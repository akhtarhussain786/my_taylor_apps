class AppointmentModel {
  final int id;
  final String appointmentCode;
  final int customerId;
  final int serviceId;
  final int addressId;
  final int? executiveId;
  final String appointmentDate;
  final String timeSlot;
  final String status;
  final String? notes;
  final String deliveryPreference;
  final String? completedAt;

  // Joined service details
  final String serviceName;
  final String serviceCategory;
  final double basePrice;
  final double expressPrice;
  final double measurementFee;

  // Joined customer details
  final String customerName;
  final String customerMobile;
  final String customerEmail;

  // Joined address details
  final String houseNo;
  final String? building;
  final String street;
  final String area;
  final String? landmark;
  final String city;
  final String pincode;
  final double? latitude;
  final double? longitude;

  AppointmentModel({
    required this.id,
    required this.appointmentCode,
    required this.customerId,
    required this.serviceId,
    required this.addressId,
    this.executiveId,
    required this.appointmentDate,
    required this.timeSlot,
    required this.status,
    this.notes,
    this.deliveryPreference = '24H_EXPRESS',
    this.completedAt,
    required this.serviceName,
    this.serviceCategory = 'men',
    this.basePrice = 599.0,
    this.expressPrice = 199.0,
    this.measurementFee = 99.0,
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

  bool get isCompleted => status == 'MEASUREMENT_COMPLETED';
  bool get is24HourExpress => deliveryPreference == '24H_EXPRESS';

  factory AppointmentModel.fromJson(Map<String, dynamic> json) {
    return AppointmentModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      appointmentCode: json['appointment_code'] ?? '',
      customerId: int.tryParse(json['customer_id'].toString()) ?? 0,
      serviceId: int.tryParse(json['service_id'].toString()) ?? 0,
      addressId: int.tryParse(json['address_id'].toString()) ?? 0,
      executiveId: json['executive_id'] != null ? int.tryParse(json['executive_id'].toString()) : null,
      appointmentDate: json['appointment_date'] ?? '',
      timeSlot: json['time_slot'] ?? '',
      status: json['status'] ?? 'BOOKED',
      notes: json['notes'],
      deliveryPreference: json['delivery_preference'] ?? '24H_EXPRESS',
      completedAt: json['completed_at'],
      serviceName: json['service_name'] ?? 'Bespoke Garment',
      serviceCategory: json['service_category'] ?? 'men',
      basePrice: double.tryParse(json['base_price']?.toString() ?? '599') ?? 599.0,
      expressPrice: double.tryParse(json['express_price']?.toString() ?? '199') ?? 199.0,
      measurementFee: double.tryParse(json['measurement_fee']?.toString() ?? '99') ?? 99.0,
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
    );
  }

  AppointmentModel copyWith({String? status, String? completedAt}) {
    return AppointmentModel(
      id: id,
      appointmentCode: appointmentCode,
      customerId: customerId,
      serviceId: serviceId,
      addressId: addressId,
      executiveId: executiveId,
      appointmentDate: appointmentDate,
      timeSlot: timeSlot,
      status: status ?? this.status,
      notes: notes,
      deliveryPreference: deliveryPreference,
      completedAt: completedAt ?? this.completedAt,
      serviceName: serviceName,
      serviceCategory: serviceCategory,
      basePrice: basePrice,
      expressPrice: expressPrice,
      measurementFee: measurementFee,
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
    );
  }
}
