class ProviderModel {
  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String? phone;
  final List<int> serviceIds;
  final String? timezone;
  final String? notes;

  const ProviderModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phone,
    this.serviceIds = const [],
    this.timezone,
    this.notes,
  });

  String get fullName => '$firstName $lastName'.trim();

  factory ProviderModel.fromJson(Map<String, dynamic> json) {
    List<int> services = [];
    if (json['services'] is List) {
      services = (json['services'] as List)
          .map((e) => e is int ? e : int.tryParse(e.toString()) ?? 0)
          .where((id) => id > 0)
          .toList();
    }

    return ProviderModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      firstName: json['firstName']?.toString() ?? json['first_name']?.toString() ?? '',
      lastName: json['lastName']?.toString() ?? json['last_name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString() ?? json['mobile']?.toString(),
      serviceIds: services,
      timezone: json['timezone']?.toString(),
      notes: json['notes']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'firstName': firstName,
      'lastName': lastName,
      'email': email,
      'phone': phone,
      'services': serviceIds,
      'timezone': timezone,
      'notes': notes,
    };
  }
}

