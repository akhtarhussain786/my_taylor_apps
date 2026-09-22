class ApiEndpoints {
  // Production Live Backend Server:
  static String baseUrl = 'https://mytaylor.in/api';

  // Local Development Fallbacks:
  // For Android emulator: http://10.0.2.2/my%20talor/api
  // For Windows / Web / iOS: http://localhost/my%20talor/api
  static void updateBaseUrl(String newUrl) {
    baseUrl = newUrl.replaceAll(RegExp(r'/+$'), '');
  }

  static String get auth => '$baseUrl/auth_api.php';
  static String get executive => '$baseUrl/executive_api.php';
  static String get delivery => '$baseUrl/delivery_api.php';
}
