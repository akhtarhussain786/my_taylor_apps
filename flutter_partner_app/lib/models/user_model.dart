enum AppRole {
  measurementExecutive,
  deliveryExecutive,
  admin,
}

extension AppRoleExtension on AppRole {
  String get value {
    switch (this) {
      case AppRole.measurementExecutive:
        return 'measurement_executive';
      case AppRole.deliveryExecutive:
        return 'delivery_executive';
      case AppRole.admin:
        return 'admin';
    }
  }

  String get displayName {
    switch (this) {
      case AppRole.measurementExecutive:
        return 'Measurement Master';
      case AppRole.deliveryExecutive:
        return 'Express Delivery Partner';
      case AppRole.admin:
        return 'Operations Admin';
    }
  }

  String get hindiName {
    switch (this) {
      case AppRole.measurementExecutive:
        return 'नाप मास्टर / टेलर';
      case AppRole.deliveryExecutive:
        return 'डिलीवरी पार्टनर';
      case AppRole.admin:
        return 'एडमिनिस्ट्रेटर';
    }
  }
}

class UserModel {
  final int id;
  final String name;
  final String email;
  final String mobile;
  final AppRole role;
  final String status;
  final String gender;
  final String? profileImage;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.mobile,
    required this.role,
    this.status = 'active',
    this.gender = 'male',
    this.profileImage,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    AppRole parsedRole = AppRole.measurementExecutive;
    final rStr = (json['role'] ?? '').toString();
    if (rStr == 'delivery_executive') {
      parsedRole = AppRole.deliveryExecutive;
    } else if (rStr == 'admin') {
      parsedRole = AppRole.admin;
    }

    return UserModel(
      id: int.tryParse(json['id'].toString()) ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      mobile: json['mobile'] ?? '',
      role: parsedRole,
      status: json['status'] ?? 'active',
      gender: json['gender'] ?? 'male',
      profileImage: json['profile_image'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'mobile': mobile,
      'role': role.value,
      'status': status,
      'gender': gender,
      'profile_image': profileImage,
    };
  }
}
