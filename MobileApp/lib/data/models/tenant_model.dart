class TenantModel {
  final int id;
  final String subdomain;
  final String companyName;
  final String? customDomain;
  final String? role; // Kullanıcının bu işletmedeki rolü (customer, provider, admin)

  const TenantModel({
    required this.id,
    required this.subdomain,
    required this.companyName,
    this.customDomain,
    this.role,
  });

  String get displayName => companyName.isNotEmpty ? companyName : subdomain;

  factory TenantModel.fromJson(Map<String, dynamic> json) {
    return TenantModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      subdomain: json['subdomain']?.toString() ?? '',
      companyName: json['company_name']?.toString() ?? json['name']?.toString() ?? json['subdomain']?.toString() ?? '',
      customDomain: json['custom_domain']?.toString(),
      role: json['role']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'subdomain': subdomain,
      'company_name': companyName,
      'custom_domain': customDomain,
      'role': role,
    };
  }
}

