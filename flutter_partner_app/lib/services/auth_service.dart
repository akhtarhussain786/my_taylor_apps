import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';

class AuthService {
  static const String _userKey = 'partner_user_profile';
  static const String _tokenKey = 'partner_auth_token';
  static const String _activeRoleKey = 'partner_active_role';
  static const String _serverUrlKey = 'partner_server_url';

  static Future<void> saveSession(UserModel user, String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_activeRoleKey, user.role.value);
  }

  static Future<UserModel?> getSavedUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userStr = prefs.getString(_userKey);
    if (userStr != null) {
      try {
        final map = jsonDecode(userStr);
        return UserModel.fromJson(map);
      } catch (_) {}
    }
    return null;
  }

  static Future<AppRole?> getSavedRole() async {
    final prefs = await SharedPreferences.getInstance();
    final roleStr = prefs.getString(_activeRoleKey);
    if (roleStr == 'delivery_executive') return AppRole.deliveryExecutive;
    if (roleStr == 'measurement_executive') return AppRole.measurementExecutive;
    if (roleStr == 'admin') return AppRole.admin;
    return null;
  }

  static Future<void> setActiveRole(AppRole role) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_activeRoleKey, role.value);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<String?> getSavedServerUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_serverUrlKey);
  }

  static Future<void> saveServerUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_serverUrlKey, url);
  }

  static Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
    await prefs.remove(_tokenKey);
    await prefs.remove(_activeRoleKey);
  }
}
