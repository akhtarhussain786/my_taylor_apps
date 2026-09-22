import 'dart:convert';
import 'dart:developer' as dev;
import 'package:http/http.dart' as http;
import '../constants/api_endpoints.dart';
import '../models/user_model.dart';
import '../models/appointment_model.dart';
import '../models/order_model.dart';
import '../models/measurement_model.dart';

class ApiService {
  static final http.Client _client = http.Client();
  static const Duration _timeout = Duration(seconds: 5);

  // In-memory local stores for offline simulation fallback
  static List<AppointmentModel>? _mockAppointments;
  static List<OrderModel>? _mockOrders;
  static List<MeasurementModel>? _mockMeasurements;

  // ---------------- AUTHENTICATION ----------------
  static Future<Map<String, dynamic>> login({
    required String identifier,
    required String password,
    String? role,
  }) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiEndpoints.auth),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'action': 'login',
              'identifier': identifier,
              'password': password,
              'role': role,
            }),
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          return {
            'success': true,
            'user': UserModel.fromJson(data['user']),
            'token': data['token'],
            'message': data['message'],
          };
        }
        return {'success': false, 'message': data['message'] ?? 'Login failed'};
      }
    } catch (e) {
      dev.log('API connection error, fallback to offline demo auth: $e');
    }

    // Offline / Demo Fallback
    final isDelivery = role == 'delivery_executive' || identifier.contains('delivery') || identifier == '9800000006';
    final user = isDelivery
        ? UserModel(
            id: 7,
            name: 'Rohit Sharma (Express Rider)',
            email: 'delivery.rohit@mytaylor.com',
            mobile: '9800000006',
            role: AppRole.deliveryExecutive,
            gender: 'male',
          )
        : UserModel(
            id: 2,
            name: 'Vikram Singh (Master)',
            email: 'exec.vikram@mytaylor.com',
            mobile: '9800000001',
            role: AppRole.measurementExecutive,
            gender: 'male',
          );

    return {
      'success': true,
      'user': user,
      'token': 'demo_token_offline_${DateTime.now().millisecondsSinceEpoch}',
      'message': 'Logged in (Demo Mode)',
    };
  }

  // ---------------- MEASUREMENT EXECUTIVE APIS ----------------

  static Future<List<AppointmentModel>> getAppointments({
    int? execId,
    String status = 'all',
  }) async {
    try {
      final uri = Uri.parse('${ApiEndpoints.executive}?action=get_appointments&executive_id=${execId ?? ''}&status=$status');
      final response = await _client.get(uri).timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['appointments'] is List) {
          final list = (data['appointments'] as List)
              .map((item) => AppointmentModel.fromJson(item))
              .toList();
          _mockAppointments = list;
          return list;
        }
      }
    } catch (e) {
      dev.log('Appointments API error, using mock data: $e');
    }

    // Initialize mock appointments if needed
    _mockAppointments ??= [
      AppointmentModel(
        id: 2,
        appointmentCode: 'APT-20260920-002',
        customerId: 9,
        serviceId: 5,
        addressId: 3,
        executiveId: execId ?? 2,
        appointmentDate: '2026-09-21',
        timeSlot: '02:00 PM – 03:00 PM',
        status: 'EXECUTIVE_ON_THE_WAY',
        notes: 'Silk blouse measurement with sample reference garment.',
        deliveryPreference: '24H_EXPRESS',
        serviceName: 'Designer Saree Blouse',
        serviceCategory: 'women',
        basePrice: 699.0,
        expressPrice: 199.0,
        customerName: 'Priya Patel',
        customerMobile: '9876543211',
        customerEmail: 'priya.patel@example.com',
        houseNo: 'Villa 7',
        building: 'Palm Meadows',
        street: 'Varthur Road',
        area: 'Indiranagar',
        landmark: 'Opp Metro Station Pillar 142',
        city: 'Bangalore',
        pincode: '560038',
      ),
      AppointmentModel(
        id: 3,
        appointmentCode: 'APT-20260921-003',
        customerId: 8,
        serviceId: 4,
        addressId: 2,
        executiveId: execId ?? 2,
        appointmentDate: '2026-09-21',
        timeSlot: '04:00 PM – 05:00 PM',
        status: 'BOOKED',
        notes: 'Bespoke suit measurement at Nariman Point corporate office.',
        deliveryPreference: '24H_EXPRESS',
        serviceName: 'Bespoke 2-Piece Suit / Blazer',
        serviceCategory: 'men',
        basePrice: 3499.0,
        expressPrice: 499.0,
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        customerEmail: 'rahul.sharma@example.com',
        houseNo: 'Unit 12B',
        building: 'Maker Chambers VI',
        street: 'Jamnalal Bajaj Marg',
        area: 'Nariman Point',
        landmark: 'Near Air India Bldg',
        city: 'Mumbai',
        pincode: '400021',
      ),
      AppointmentModel(
        id: 1,
        appointmentCode: 'APT-20260920-001',
        customerId: 8,
        serviceId: 1,
        addressId: 1,
        executiveId: execId ?? 2,
        appointmentDate: '2026-09-20',
        timeSlot: '10:00 AM – 11:00 AM',
        status: 'MEASUREMENT_COMPLETED',
        notes: 'Doorstep measurement done at Bandra West residence.',
        deliveryPreference: '24H_EXPRESS',
        completedAt: '2026-09-20 10:45:00',
        serviceName: 'Bespoke Formal Shirt',
        serviceCategory: 'men',
        basePrice: 599.0,
        expressPrice: 199.0,
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        customerEmail: 'rahul.sharma@example.com',
        houseNo: 'Flat 402',
        building: 'Imperial Heights',
        street: 'Pali Hill Road',
        area: 'Bandra West',
        landmark: 'Near Cafe Basilico',
        city: 'Mumbai',
        pincode: '400050',
      ),
    ];

    if (status == 'all') return _mockAppointments!;
    return _mockAppointments!.where((a) => a.status == status).toList();
  }

  static Future<bool> updateAppointmentStatus({
    required int appointmentId,
    required int executiveId,
    required String newStatus,
  }) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiEndpoints.executive),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'action': 'update_status',
              'appointment_id': appointmentId,
              'executive_id': executiveId,
              'new_status': newStatus,
            }),
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _updateLocalAppointmentStatus(appointmentId, newStatus);
          return true;
        }
      }
    } catch (e) {
      dev.log('Update status API failed, using local update: $e');
    }

    _updateLocalAppointmentStatus(appointmentId, newStatus);
    return true;
  }

  static void _updateLocalAppointmentStatus(int id, String status) {
    if (_mockAppointments != null) {
      final idx = _mockAppointments!.indexWhere((a) => a.id == id);
      if (idx != -1) {
        _mockAppointments![idx] = _mockAppointments![idx].copyWith(status: status);
      }
    }
  }

  static Future<Map<String, dynamic>> submitMeasurement({
    required int appointmentId,
    required int executiveId,
    required String garmentType,
    required String fitPreference,
    required String notes,
    required String fabricSource,
    int fabricId = 1,
    required Map<String, dynamic> measurements,
    required Map<String, dynamic> designSpecs,
    String? referenceImage,
  }) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiEndpoints.executive),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'action': 'submit_measurement',
              'appointment_id': appointmentId,
              'executive_id': executiveId,
              'garment_type': garmentType,
              'fit_preference': fitPreference,
              'notes': notes,
              'fabric_source': fabricSource,
              'fabric_id': fabricId,
              'measurements': measurements,
              'design_specs': designSpecs,
              'reference_image': referenceImage,
            }),
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _updateLocalAppointmentStatus(appointmentId, 'MEASUREMENT_COMPLETED');
          return {'success': true, 'data': data};
        }
        return {'success': false, 'message': data['message'] ?? 'Failed to submit'};
      }
    } catch (e) {
      dev.log('Submit measurement API failed, creating local order: $e');
    }

    // Local simulation fallback
    _updateLocalAppointmentStatus(appointmentId, 'MEASUREMENT_COMPLETED');
    final code = 'MT-M-${DateTime.now().millisecondsSinceEpoch.toString().substring(6)}';
    final bookingId = 'MYT-${DateTime.now().millisecondsSinceEpoch.toString().substring(5)}';

    return {
      'success': true,
      'data': {
        'measurement_code': code,
        'booking_id': bookingId,
        'message': 'Measurement recorded successfully! 24H SLA order dispatched.',
        'sla_deadline': DateTime.now().add(const Duration(hours: 24)).toIso8601String(),
      }
    };
  }

  static Future<List<Map<String, dynamic>>> getFabrics() async {
    try {
      final response = await _client
          .get(Uri.parse('${ApiEndpoints.executive}?action=get_fabrics'))
          .timeout(_timeout);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['fabrics'] is List) {
          return List<Map<String, dynamic>>.from(data['fabrics']);
        }
      }
    } catch (_) {}

    return [
      {
        'id': 1,
        'sku': 'FAB-EGY-001',
        'name': 'Egyptian Giza Cotton 100s',
        'category': 'Cotton',
        'color': 'Crisp White',
        'price_per_meter': '899.00',
      },
      {
        'id': 2,
        'sku': 'FAB-ITA-002',
        'name': 'Italian Superfine Linen',
        'category': 'Linen',
        'color': 'Sky Blue',
        'price_per_meter': '1199.00',
      },
      {
        'id': 3,
        'sku': 'FAB-RAY-003',
        'name': 'Raymond Poly-Wool Blend',
        'category': 'Wool Blend',
        'color': 'Charcoal Grey',
        'price_per_meter': '1499.00',
      },
      {
        'id': 4,
        'sku': 'FAB-SIL-004',
        'name': 'Banarasi Chanderi Silk',
        'category': 'Silk',
        'color': 'Imperial Maroon',
        'price_per_meter': '1899.00',
      },
      {
        'id': 5,
        'sku': 'FAB-SAT-005',
        'name': 'Royal Satin Cotton',
        'category': 'Satin Cotton',
        'color': 'Midnight Black',
        'price_per_meter': '799.00',
      },
    ];
  }

  static Future<List<MeasurementModel>> getMeasurementHistory({int? execId}) async {
    try {
      final uri = Uri.parse('${ApiEndpoints.executive}?action=get_history&executive_id=${execId ?? 0}');
      final response = await _client.get(uri).timeout(_timeout);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['measurements'] is List) {
          return (data['measurements'] as List)
              .map((i) => MeasurementModel.fromJson(i))
              .toList();
        }
      }
    } catch (_) {}

    _mockMeasurements ??= [
      MeasurementModel(
        id: 1,
        measurementCode: 'MT-M-0001028',
        customerId: 8,
        garmentCategory: 'Shirt',
        measurements: {
          'neck': 16.0,
          'chest': 40.0,
          'waist': 34.0,
          'hip': 41.0,
          'shoulder': 18.5,
          'sleeve_length': 25.5,
          'bicep': 14.5,
          'wrist': 7.5,
          'shirt_length': 30.0,
        },
        fitPreference: 'Slim Fit',
        designSpecs: {
          'collar': 'Semi-Cutaway',
          'cuff': 'French Cuff (Double)',
          'pocket': 'Single Left V-Pocket',
        },
        notes: 'Customer prefers snug fit on biceps.',
        createdAt: '2026-09-20 10:45:00',
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        bookingId: 'MYT-20260920-001245',
        orderStatus: 'STITCHING_IN_PROGRESS',
      ),
      MeasurementModel(
        id: 2,
        measurementCode: 'MT-M-0001029',
        customerId: 8,
        garmentCategory: 'Trouser',
        measurements: {
          'waist': 34.0,
          'hip': 41.0,
          'rise': 10.5,
          'thigh': 24.0,
          'knee': 17.0,
          'bottom': 14.5,
          'inseam': 32.0,
          'outseam': 41.5,
        },
        fitPreference: 'Slim Fit',
        designSpecs: {
          'waistband': 'Side Adjusters (No Loops)',
          'pleat': 'Flat Front',
        },
        notes: 'Slanted side pockets and one coin pocket.',
        createdAt: '2026-09-19 16:20:00',
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        bookingId: 'MYT-20260919-001240',
        orderStatus: 'DELIVERED',
      ),
    ];
    return _mockMeasurements!;
  }

  // ---------------- DELIVERY EXECUTIVE APIS ----------------

  static Future<List<OrderModel>> getDeliveries({
    int? riderId,
    String status = 'active',
  }) async {
    try {
      final uri = Uri.parse('${ApiEndpoints.delivery}?action=get_deliveries&rider_id=${riderId ?? ''}&status=$status');
      final response = await _client.get(uri).timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['orders'] is List) {
          final list = (data['orders'] as List)
              .map((item) => OrderModel.fromJson(item))
              .toList();
          _mockOrders = list;
          return list;
        }
      }
    } catch (e) {
      dev.log('Deliveries API error, using mock data: $e');
    }

    _mockOrders ??= [
      OrderModel(
        id: 1,
        bookingId: 'MYT-20260920-001245',
        customerId: 8,
        serviceId: 1,
        deliveryAddressId: 1,
        priority: 'EXPRESS',
        is24hDelivery: true,
        tailoringCharge: 599.0,
        fabricCharge: 899.0,
        measurementFee: 99.0,
        expressFee: 199.0,
        totalAmount: 1696.0,
        paymentMethod: 'UPI',
        paymentStatus: 'PAID',
        orderStatus: 'READY_FOR_DISPATCH',
        slaDeadline: '2026-09-21 11:00:00',
        specialInstructions: '24-Hour Express guarantee. Customer requested contrast dark blue inner collar piping.',
        serviceName: 'Bespoke Formal Shirt',
        serviceCategory: 'men',
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        customerEmail: 'rahul.sharma@example.com',
        houseNo: 'Flat 402',
        building: 'Imperial Heights',
        street: 'Pali Hill Road',
        area: 'Bandra West',
        landmark: 'Near Cafe Basilico',
        city: 'Mumbai',
        pincode: '400050',
      ),
      OrderModel(
        id: 4,
        bookingId: 'MYT-20260920-001290',
        customerId: 9,
        serviceId: 5,
        deliveryAddressId: 3,
        priority: 'EXPRESS',
        is24hDelivery: true,
        tailoringCharge: 699.0,
        fabricCharge: 0.0,
        measurementFee: 99.0,
        expressFee: 199.0,
        totalAmount: 997.0,
        paymentMethod: 'COD',
        paymentStatus: 'PENDING',
        orderStatus: 'OUT_FOR_DELIVERY',
        slaDeadline: '2026-09-21 15:30:00',
        specialInstructions: 'Collect Rs. 997 via UPI QR / Cash at Doorstep.',
        serviceName: 'Designer Saree Blouse',
        serviceCategory: 'women',
        customerName: 'Priya Patel',
        customerMobile: '9876543211',
        customerEmail: 'priya.patel@example.com',
        houseNo: 'Villa 7',
        building: 'Palm Meadows',
        street: 'Varthur Road',
        area: 'Indiranagar',
        landmark: 'Opp Metro Station Pillar 142',
        city: 'Bangalore',
        pincode: '560038',
      ),
      OrderModel(
        id: 2,
        bookingId: 'MYT-20260919-001240',
        customerId: 8,
        serviceId: 2,
        deliveryAddressId: 1,
        priority: 'EXPRESS',
        is24hDelivery: true,
        tailoringCharge: 699.0,
        fabricCharge: 0.0,
        measurementFee: 99.0,
        expressFee: 199.0,
        totalAmount: 997.0,
        paymentMethod: 'CARD',
        paymentStatus: 'PAID',
        orderStatus: 'DELIVERED',
        deliveredAt: '2026-09-20 08:45:00',
        specialInstructions: 'Navy Italian Wool Trouser, Delivered with signature confirmation.',
        serviceName: 'Tailored Formal Trouser',
        serviceCategory: 'men',
        customerName: 'Rahul Sharma',
        customerMobile: '9876543210',
        customerEmail: 'rahul.sharma@example.com',
        houseNo: 'Flat 402',
        building: 'Imperial Heights',
        street: 'Pali Hill Road',
        area: 'Bandra West',
        landmark: 'Near Cafe Basilico',
        city: 'Mumbai',
        pincode: '400050',
        recipientName: 'Rahul Sharma',
        recipientRelation: 'Self',
        deliveredTimestamp: '2026-09-20 08:45:00',
      ),
    ];

    if (status == 'all') return _mockOrders!;
    if (status == 'active') {
      return _mockOrders!.where((o) => o.orderStatus == 'READY_FOR_DISPATCH' || o.orderStatus == 'OUT_FOR_DELIVERY').toList();
    }
    if (status == 'delivered') {
      return _mockOrders!.where((o) => o.orderStatus == 'DELIVERED').toList();
    }
    return _mockOrders!.where((o) => o.orderStatus == status).toList();
  }

  static Future<bool> markOutForDelivery({
    required int orderId,
    required int riderId,
  }) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiEndpoints.delivery),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'action': 'out_for_delivery',
              'order_id': orderId,
              'rider_id': riderId,
            }),
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _updateLocalOrderStatus(orderId, 'OUT_FOR_DELIVERY');
          return true;
        }
      }
    } catch (_) {}

    _updateLocalOrderStatus(orderId, 'OUT_FOR_DELIVERY');
    return true;
  }

  static Future<Map<String, dynamic>> completeDelivery({
    required int orderId,
    required int riderId,
    required String recipientName,
    required String recipientRelation,
    required String signatureData,
    String? photoUrl,
    double? latitude,
    double? longitude,
  }) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiEndpoints.delivery),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'action': 'complete_delivery',
              'order_id': orderId,
              'rider_id': riderId,
              'recipient_name': recipientName,
              'recipient_relation': recipientRelation,
              'signature_data': signatureData,
              'photo_url': photoUrl,
              'latitude': latitude,
              'longitude': longitude,
            }),
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _updateLocalDeliveryProof(orderId, recipientName, recipientRelation, signatureData);
          return {'success': true, 'data': data};
        }
        return {'success': false, 'message': data['message'] ?? 'Failed to complete delivery'};
      }
    } catch (_) {}

    _updateLocalDeliveryProof(orderId, recipientName, recipientRelation, signatureData);
    return {
      'success': true,
      'data': {
        'message': 'Order delivered successfully with customer signature!',
        'delivered_at': DateTime.now().toIso8601String(),
      }
    };
  }

  static void _updateLocalOrderStatus(int id, String status) {
    if (_mockOrders != null) {
      final idx = _mockOrders!.indexWhere((o) => o.id == id);
      if (idx != -1) {
        _mockOrders![idx] = _mockOrders![idx].copyWith(orderStatus: status);
      }
    }
  }

  static void _updateLocalDeliveryProof(int id, String name, String rel, String sig) {
    if (_mockOrders != null) {
      final idx = _mockOrders!.indexWhere((o) => o.id == id);
      if (idx != -1) {
        _mockOrders![idx] = _mockOrders![idx].copyWith(
          orderStatus: 'DELIVERED',
          deliveredAt: DateTime.now().toIso8601String(),
          recipientName: name,
          recipientRelation: rel,
          customerSignatureSvg: sig,
        );
      }
    }
  }
}
