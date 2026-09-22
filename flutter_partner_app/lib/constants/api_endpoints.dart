class ApiEndpoints {
  // Default base URL for local XAMPP backend.
  // For Android emulator: http://10.0.2.2/my%20talor/api
  // For Windows / Web / iOS simulator: http://localhost/my%20talor/api
  // For physical phone on same Wi-Fi: http://192.168.x.x/my%20talor/api
  static String baseUrl = 'http://10.0.2.2/my%20talor/api';

  // Helper method to set custom server IP or URL from settings
  static void updateBaseUrl(String newUrl) {
    baseUrl = newUrl.replaceAll(RegExp(r'/+$'), '');
  }

  static String get auth => '$baseUrl/auth_api.php';
  static String get executive => '$baseUrl/executive_api.php';
  static String get delivery => '$baseUrl/delivery_api.php';
}
