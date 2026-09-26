class UserModel {
  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String? phoneNumber;
  final String role; // 'customer', 'provider', 'admin', 'secretary'
  final String? timezone;
  final String? language;

  const UserModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phoneNumber,
    required this.role,
    this.timezone,
    this.language,
  });

  String get fullName {
    final name = '$firstName $lastName'.trim();
    return name.isNotEmpty ? name : (email.isNotEmpty ? email : 'Kullanıcı #$id');
  }

  String get name => fullName;
  String get phone => phoneNumber ?? '';

  bool get isCustomer => role.toLowerCase() == 'customer';
  bool get isProvider => role.toLowerCase() == 'provider';
  bool get isAdmin => role.toLowerCase() == 'admin';
  bool get isSecretary => role.toLowerCase() == 'secretary';
  bool get isStaff => !isCustomer;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      firstName: json['first_name']?.toString() ?? json['firstName']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? json['lastName']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phoneNumber: json['phone_number']?.toString() ?? json['phone']?.toString(),
      role: json['role']?.toString() ?? json['role_slug']?.toString() ?? 'customer',
      timezone: json['timezone']?.toString(),
      language: json['language']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone_number': phoneNumber,
      'role': role,
      'timezone': timezone,
      'language': language,
    };
  }

  UserModel copyWith({
    int? id,
    String? firstName,
    String? lastName,
    String? email,
    String? phoneNumber,
    String? role,
    String? timezone,
    String? language,
  }) {
    return UserModel(
      id: id ?? this.id,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      email: email ?? this.email,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      role: role ?? this.role,
      timezone: timezone ?? this.timezone,
      language: language ?? this.language,
    );
  }
}

