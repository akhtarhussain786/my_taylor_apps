import 'dart:convert';

class MeasurementModel {
  final int id;
  final String measurementCode;
  final int customerId;
  final String garmentCategory;
  final Map<String, dynamic> measurements;
  final String fitPreference;
  final Map<String, dynamic> designSpecs;
  final String? referenceImage;
  final String? notes;
  final int? createdByUserId;
  final String createdAt;

  // Joined fields
  final String? customerName;
  final String? customerMobile;
  final String? bookingId;
  final String? orderStatus;

  MeasurementModel({
    required this.id,
    required this.measurementCode,
    required this.customerId,
    required this.garmentCategory,
    required this.measurements,
    this.fitPreference = 'Regular Fit',
    required this.designSpecs,
    this.referenceImage,
    this.notes,
    this.createdByUserId,
    this.createdAt = '',
    this.customerName,
    this.customerMobile,
    this.bookingId,
    this.orderStatus,
  });

  factory MeasurementModel.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic> parsedMeasurements = {};
    if (json['measurements_json'] != null) {
      if (json['measurements_json'] is String) {
        try {
          parsedMeasurements = jsonDecode(json['measurements_json']);
        } catch (_) {}
      } else if (json['measurements_json'] is Map) {
        parsedMeasurements = Map<String, dynamic>.from(json['measurements_json']);
      }
    }

    Map<String, dynamic> parsedSpecs = {};
    if (json['design_specs_json'] != null) {
      if (json['design_specs_json'] is String) {
        try {
          parsedSpecs = jsonDecode(json['design_specs_json']);
        } catch (_) {}
      } else if (json['design_specs_json'] is Map) {
        parsedSpecs = Map<String, dynamic>.from(json['design_specs_json']);
      }
    }

    return MeasurementModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      measurementCode: json['measurement_code'] ?? '',
      customerId: int.tryParse(json['customer_id'].toString()) ?? 0,
      garmentCategory: json['garment_category'] ?? 'Shirt',
      measurements: parsedMeasurements,
      fitPreference: json['fit_preference'] ?? 'Regular Fit',
      designSpecs: parsedSpecs,
      referenceImage: json['reference_image'],
      notes: json['notes'],
      createdByUserId: json['created_by_user_id'] != null ? int.tryParse(json['created_by_user_id'].toString()) : null,
      createdAt: json['created_at'] ?? '',
      customerName: json['customer_name'],
      customerMobile: json['customer_mobile'],
      bookingId: json['booking_id'],
      orderStatus: json['order_status'],
    );
  }
}
