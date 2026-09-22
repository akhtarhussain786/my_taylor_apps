import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../models/appointment_model.dart';
import '../models/order_model.dart';
import '../models/measurement_model.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';

class AppState extends ChangeNotifier {
  UserModel? _currentUser;
  AppRole _activeRole = AppRole.measurementExecutive;
  bool _isLoading = false;
  String? _errorMessage;
  bool _isOnDuty = true;

  List<AppointmentModel> _appointments = [];
  String _appointmentFilter = 'all';

  List<OrderModel> _deliveries = [];
  String _deliveryFilter = 'active';

  List<MeasurementModel> _measurementHistory = [];
  List<Map<String, dynamic>> _fabrics = [];

  // Getters
  UserModel? get currentUser => _currentUser;
  AppRole get activeRole => _activeRole;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  bool get isOnDuty => _isOnDuty;
  bool get isAuthenticated => _currentUser != null;

  List<AppointmentModel> get appointments => _appointments;
  String get appointmentFilter => _appointmentFilter;

  List<OrderModel> get deliveries => _deliveries;
  String get deliveryFilter => _deliveryFilter;

  List<MeasurementModel> get measurementHistory => _measurementHistory;
  List<Map<String, dynamic>> get fabrics => _fabrics;

  // Counters
  int get pendingAppointmentsCount => _appointments.where((a) => !a.isCompleted).length;
  int get completedAppointmentsCount => _appointments.where((a) => a.isCompleted).length;
  int get pendingDeliveriesCount => _deliveries.where((d) => d.orderStatus == 'READY_FOR_DISPATCH' || d.orderStatus == 'OUT_FOR_DELIVERY').length;
  int get completedDeliveriesCount => _deliveries.where((d) => d.orderStatus == 'DELIVERED').length;

  Future<void> init() async {
    _isLoading = true;
    notifyListeners();

    try {
      final savedUser = await AuthService.getSavedUser();
      final savedRole = await AuthService.getSavedRole();

      if (savedUser != null) {
        _currentUser = savedUser;
        _activeRole = savedRole ?? savedUser.role;
        await refreshData();
      } else {
        _currentUser = null;
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void toggleDuty() {
    _isOnDuty = !_isOnDuty;
    notifyListeners();
  }

  Future<void> switchRole(AppRole newRole) async {
    _activeRole = newRole;
    await AuthService.setActiveRole(newRole);
    await refreshData();
    notifyListeners();
  }

  Future<bool> login({
    required String identifier,
    required String password,
    String? role,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.login(
        identifier: identifier,
        password: password,
        role: role,
      );

      if (result['success'] == true && result['user'] != null) {
        _currentUser = result['user'] as UserModel;
        _activeRole = _currentUser!.role;
        await AuthService.saveSession(_currentUser!, result['token'] ?? '');
        await refreshData();
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'] ?? 'Login failed';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await AuthService.clearSession();
    _currentUser = null;
    _appointments.clear();
    _deliveries.clear();
    notifyListeners();
  }

  Future<void> refreshData() async {
    if (_activeRole == AppRole.measurementExecutive) {
      await fetchAppointments();
      await fetchFabrics();
      await fetchMeasurementHistory();
    } else {
      await fetchDeliveries();
    }
  }

  // Measurement Executive Actions
  Future<void> fetchAppointments({String? status}) async {
    if (status != null) _appointmentFilter = status;
    try {
      _appointments = await ApiService.getAppointments(
        execId: _currentUser?.id,
        status: _appointmentFilter,
      );
    } catch (e) {
      _errorMessage = e.toString();
    }
    notifyListeners();
  }

  Future<bool> updateAppointmentStatus(int appointmentId, String newStatus) async {
    final success = await ApiService.updateAppointmentStatus(
      appointmentId: appointmentId,
      executiveId: _currentUser?.id ?? 2,
      newStatus: newStatus,
    );
    if (success) {
      await fetchAppointments();
    }
    return success;
  }

  Future<Map<String, dynamic>> submitMeasurement({
    required int appointmentId,
    required String garmentType,
    required String fitPreference,
    required String notes,
    required String fabricSource,
    int fabricId = 1,
    required Map<String, dynamic> measurements,
    required Map<String, dynamic> designSpecs,
    String? referenceImage,
  }) async {
    final result = await ApiService.submitMeasurement(
      appointmentId: appointmentId,
      executiveId: _currentUser?.id ?? 2,
      garmentType: garmentType,
      fitPreference: fitPreference,
      notes: notes,
      fabricSource: fabricSource,
      fabricId: fabricId,
      measurements: measurements,
      designSpecs: designSpecs,
      referenceImage: referenceImage,
    );
    if (result['success'] == true) {
      await fetchAppointments();
      await fetchMeasurementHistory();
    }
    return result;
  }

  Future<void> fetchFabrics() async {
    try {
      _fabrics = await ApiService.getFabrics();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> fetchMeasurementHistory() async {
    try {
      _measurementHistory = await ApiService.getMeasurementHistory(execId: _currentUser?.id);
      notifyListeners();
    } catch (_) {}
  }

  // Delivery Executive Actions
  Future<void> fetchDeliveries({String? status}) async {
    if (status != null) _deliveryFilter = status;
    try {
      _deliveries = await ApiService.getDeliveries(
        riderId: _currentUser?.id,
        status: _deliveryFilter,
      );
    } catch (e) {
      _errorMessage = e.toString();
    }
    notifyListeners();
  }

  Future<bool> markOutForDelivery(int orderId) async {
    final success = await ApiService.markOutForDelivery(
      orderId: orderId,
      riderId: _currentUser?.id ?? 7,
    );
    if (success) {
      await fetchDeliveries();
    }
    return success;
  }

  Future<Map<String, dynamic>> completeDelivery({
    required int orderId,
    required String recipientName,
    required String recipientRelation,
    required String signatureData,
    String? photoUrl,
    double? latitude,
    double? longitude,
  }) async {
    final result = await ApiService.completeDelivery(
      orderId: orderId,
      riderId: _currentUser?.id ?? 7,
      recipientName: recipientName,
      recipientRelation: recipientRelation,
      signatureData: signatureData,
      photoUrl: photoUrl,
      latitude: latitude,
      longitude: longitude,
    );
    if (result['success'] == true) {
      await fetchDeliveries();
    }
    return result;
  }
}
