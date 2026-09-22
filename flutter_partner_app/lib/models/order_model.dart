class OrderModel {
  final int id;
  final String bookingId;
  final int customerId;
  final int? appointmentId;
  final int? measurementId;
  final int serviceId;
  final String fabricSource;
  final int? fabricId;
  final int deliveryAddressId;
  final String priority;
  final bool is24hDelivery;
  final double tailoringCharge;
  final double fabricCharge;
  final double measurementFee;
  final double expressFee;
  final double totalAmount;
  final String paymentMethod;
  final String paymentStatus;
  final String orderStatus;
  final String? slaStartTime;
  final String? slaDeadline;
  final String? deliveredAt;
  final int? assignedDeliveryId;
  final String? specialInstructions;
  final String createdAt;

  // Joined fields
  final String serviceName;
  final String serviceCategory;
  final String customerName;
  final String customerMobile;
  final String customerEmail;
  final String houseNo;
  final String? building;
  final String street;
  final String area;
  final String? landmark;
  final String city;
  final String pincode;
  final double? latitude;
  final double? longitude;

  // Proof fields
  final String? customerSignatureSvg;
  final String? recipientName;
  final String? recipientRelation;
  final String? deliveredTimestamp;

  OrderModel({
    required this.id,
    required this.bookingId,
    required this.customerId,
    this.appointmentId,
    this.measurementId,
    required this.serviceId,
    this.fabricSource = 'MY_TAYLOR_FABRIC',
    this.fabricId,
    required this.deliveryAddressId,
    this.priority = 'EXPRESS',
    this.is24hDelivery = true,
    this.tailoringCharge = 599.0,
    this.fabricCharge = 0.0,
    this.measurementFee = 99.0,
    this.expressFee = 199.0,
    required this.totalAmount,
    this.paymentMethod = 'UPI',
    this.paymentStatus = 'PAID',
    required this.orderStatus,
    this.slaStartTime,
    this.slaDeadline,
    this.deliveredAt,
    this.assignedDeliveryId,
    this.specialInstructions,
    this.createdAt = '',
    required this.serviceName,
    this.serviceCategory = 'men',
    required this.customerName,
    required this.customerMobile,
    this.customerEmail = '',
    required this.houseNo,
    this.building,
    required this.street,
    required this.area,
    this.landmark,
    this.city = 'Mumbai',
    required this.pincode,
    this.latitude,
    this.longitude,
    this.customerSignatureSvg,
    this.recipientName,
    this.recipientRelation,
    this.deliveredTimestamp,
  });

  String get fullAddress {
    final parts = [
      houseNo,
      if (building != null && building!.isNotEmpty) building,
      street,
      area,
      if (landmark != null && landmark!.isNotEmpty) 'Near $landmark',
      '$city - $pincode',
    ];
    return parts.where((p) => p != null && p.isNotEmpty).join(', ');
  }

  bool get isDelivered => orderStatus == 'DELIVERED';
  bool get isOutForDelivery => orderStatus == 'OUT_FOR_DELIVERY';
  bool get isReadyForDispatch => orderStatus == 'READY_FOR_DISPATCH';
  bool get isCod => paymentMethod == 'COD' && paymentStatus != 'PAID';

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      bookingId: json['booking_id'] ?? '',
      customerId: int.tryParse(json['customer_id'].toString()) ?? 0,
      appointmentId: json['appointment_id'] != null ? int.tryParse(json['appointment_id'].toString()) : null,
      measurementId: json['measurement_id'] != null ? int.tryParse(json['measurement_id'].toString()) : null,
      serviceId: int.tryParse(json['service_id'].toString()) ?? 0,
      fabricSource: json['fabric_source'] ?? 'MY_TAYLOR_FABRIC',
      fabricId: json['fabric_id'] != null ? int.tryParse(json['fabric_id'].toString()) : null,
      deliveryAddressId: int.tryParse(json['delivery_address_id'].toString()) ?? 0,
      priority: json['priority'] ?? 'EXPRESS',
      is24hDelivery: json['is_24h_delivery'] == 1 || json['is_24h_delivery'] == '1' || json['is_24h_delivery'] == true,
      tailoringCharge: double.tryParse(json['tailoring_charge']?.toString() ?? '599') ?? 599.0,
      fabricCharge: double.tryParse(json['fabric_charge']?.toString() ?? '0') ?? 0.0,
      measurementFee: double.tryParse(json['measurement_fee']?.toString() ?? '99') ?? 99.0,
      expressFee: double.tryParse(json['express_fee']?.toString() ?? '199') ?? 199.0,
      totalAmount: double.tryParse(json['total_amount']?.toString() ?? '897') ?? 897.0,
      paymentMethod: json['payment_method'] ?? 'UPI',
      paymentStatus: json['payment_status'] ?? 'PAID',
      orderStatus: json['order_status'] ?? 'READY_FOR_DISPATCH',
      slaStartTime: json['sla_start_time'],
      slaDeadline: json['sla_deadline'],
      deliveredAt: json['delivered_at'],
      assignedDeliveryId: json['assigned_delivery_id'] != null ? int.tryParse(json['assigned_delivery_id'].toString()) : null,
      specialInstructions: json['special_instructions'],
      createdAt: json['created_at'] ?? '',
      serviceName: json['service_name'] ?? 'Bespoke Garment',
      serviceCategory: json['service_category'] ?? 'men',
      customerName: json['customer_name'] ?? 'Customer',
      customerMobile: json['customer_mobile'] ?? '',
      customerEmail: json['customer_email'] ?? '',
      houseNo: json['house_no'] ?? '',
      building: json['building'],
      street: json['street'] ?? '',
      area: json['area'] ?? '',
      landmark: json['landmark'],
      city: json['city'] ?? 'Mumbai',
      pincode: json['pincode'] ?? '',
      latitude: json['latitude'] != null ? double.tryParse(json['latitude'].toString()) : null,
      longitude: json['longitude'] != null ? double.tryParse(json['longitude'].toString()) : null,
      customerSignatureSvg: json['customer_signature_svg'],
      recipientName: json['recipient_name'],
      recipientRelation: json['recipient_relation'],
      deliveredTimestamp: json['delivered_timestamp'],
    );
  }

  OrderModel copyWith({
    String? orderStatus,
    String? deliveredAt,
    String? recipientName,
    String? recipientRelation,
    String? customerSignatureSvg,
  }) {
    return OrderModel(
      id: id,
      bookingId: bookingId,
      customerId: customerId,
      appointmentId: appointmentId,
      measurementId: measurementId,
      serviceId: serviceId,
      fabricSource: fabricSource,
      fabricId: fabricId,
      deliveryAddressId: deliveryAddressId,
      priority: priority,
      is24hDelivery: is24hDelivery,
      tailoringCharge: tailoringCharge,
      fabricCharge: fabricCharge,
      measurementFee: measurementFee,
      expressFee: expressFee,
      totalAmount: totalAmount,
      paymentMethod: paymentMethod,
      paymentStatus: paymentStatus,
      orderStatus: orderStatus ?? this.orderStatus,
      slaStartTime: slaStartTime,
      slaDeadline: slaDeadline,
      deliveredAt: deliveredAt ?? this.deliveredAt,
      assignedDeliveryId: assignedDeliveryId,
      specialInstructions: specialInstructions,
      createdAt: createdAt,
      serviceName: serviceName,
      serviceCategory: serviceCategory,
      customerName: customerName,
      customerMobile: customerMobile,
      customerEmail: customerEmail,
      houseNo: houseNo,
      building: building,
      street: street,
      area: area,
      landmark: landmark,
      city: city,
      pincode: pincode,
      latitude: latitude,
      longitude: longitude,
      customerSignatureSvg: customerSignatureSvg ?? this.customerSignatureSvg,
      recipientName: recipientName ?? this.recipientName,
      recipientRelation: recipientRelation ?? this.recipientRelation,
      deliveredTimestamp: deliveredTimestamp,
    );
  }
}
