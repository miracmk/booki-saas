class CustomerCrmModel {
  final int customerId;
  final String name;
  final String firstName;
  final String lastName;
  final String phone;
  final String cleanPhone;
  final String email;
  final String notes;
  final String allergyNotes;
  final int totalVisits;
  final int totalAppointments;
  final int noShows;
  final int cancelled;
  final int noShowScore; // 0-100
  final double totalSpent;
  final String currency;
  final String? whatsappUrl;
  final String? callUrl;
  final List<Map<String, dynamic>> pastAppointments;

  const CustomerCrmModel({
    required this.customerId,
    required this.name,
    this.firstName = '',
    this.lastName = '',
    this.phone = '',
    this.cleanPhone = '',
    this.email = '',
    this.notes = '',
    this.allergyNotes = '',
    this.totalVisits = 0,
    this.totalAppointments = 0,
    this.noShows = 0,
    this.cancelled = 0,
    this.noShowScore = 100,
    this.totalSpent = 0.0,
    this.currency = '₺',
    this.whatsappUrl,
    this.callUrl,
    this.pastAppointments = const [],
  });

  factory CustomerCrmModel.fromJson(Map<String, dynamic> json) {
    return CustomerCrmModel(
      customerId: json['customer_id'] is int ? json['customer_id'] as int : int.tryParse(json['customer_id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? 'Müşteri',
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      cleanPhone: json['clean_phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      notes: json['notes']?.toString() ?? '',
      allergyNotes: json['allergy_notes']?.toString() ?? 'Özel bir alerji/not bulunmamaktadır.',
      totalVisits: json['total_visits'] is int ? json['total_visits'] as int : int.tryParse(json['total_visits']?.toString() ?? '0') ?? 0,
      totalAppointments: json['total_appointments'] is int ? json['total_appointments'] as int : int.tryParse(json['total_appointments']?.toString() ?? '0') ?? 0,
      noShows: json['no_shows'] is int ? json['no_shows'] as int : int.tryParse(json['no_shows']?.toString() ?? '0') ?? 0,
      cancelled: json['cancelled'] is int ? json['cancelled'] as int : int.tryParse(json['cancelled']?.toString() ?? '0') ?? 0,
      noShowScore: json['no_show_score'] is int ? json['no_show_score'] as int : int.tryParse(json['no_show_score']?.toString() ?? '100') ?? 100,
      totalSpent: (json['total_spent'] is num ? (json['total_spent'] as num).toDouble() : double.tryParse(json['total_spent']?.toString() ?? '0.0') ?? 0.0),
      currency: json['currency']?.toString() ?? '₺',
      whatsappUrl: json['whatsapp_url']?.toString(),
      callUrl: json['call_url']?.toString(),
      pastAppointments: json['past_appointments'] is List ? List<Map<String, dynamic>>.from(json['past_appointments']) : const [],
    );
  }
}
