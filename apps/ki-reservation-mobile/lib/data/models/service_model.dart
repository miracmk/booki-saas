class ServiceModel {
  final int id;
  final String name;
  final int duration; // dakika cinsinden
  final double price;
  final String currency;
  final String? description;
  final String? location;
  final String? color;
  final int slotInterval;
  final int attendantsNumber;
  final bool isPrivate;
  final int? serviceCategoryId;

  const ServiceModel({
    required this.id,
    required this.name,
    required this.duration,
    required this.price,
    required this.currency,
    this.description,
    this.location,
    this.color,
    this.slotInterval = 15,
    this.attendantsNumber = 1,
    this.isPrivate = false,
    this.serviceCategoryId,
  });

  String get formattedPrice {
    if (price <= 0) return 'Ücretsiz';
    final curr = currency.isEmpty ? '₺' : (currency == 'TRY' ? '₺' : currency);
    return '${price.toStringAsFixed(price.truncateToDouble() == price ? 0 : 2)} $curr';
  }

  String get formattedDuration {
    if (duration >= 60) {
      final hours = duration ~/ 60;
      final mins = duration % 60;
      if (mins == 0) return '$hours saat';
      return '$hours sa $mins dk';
    }
    return '$duration dk';
  }

  factory ServiceModel.fromJson(Map<String, dynamic> json) {
    return ServiceModel(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      duration: json['duration'] is int
          ? json['duration'] as int
          : int.tryParse(json['duration']?.toString() ?? '30') ?? 30,
      price: json['price'] is num
          ? (json['price'] as num).toDouble()
          : double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      currency: json['currency']?.toString() ?? 'TRY',
      description: json['description']?.toString(),
      location: json['location']?.toString(),
      color: json['color']?.toString(),
      slotInterval: json['slotInterval'] is int
          ? json['slotInterval'] as int
          : int.tryParse(json['slotInterval']?.toString() ?? '15') ?? 15,
      attendantsNumber: json['attendantsNumber'] is int
          ? json['attendantsNumber'] as int
          : int.tryParse(json['attendantsNumber']?.toString() ?? '1') ?? 1,
      isPrivate: json['isPrivate'] == true || json['is_private'] == 1 || json['is_private'] == '1',
      serviceCategoryId: json['serviceCategoryId'] is int
          ? json['serviceCategoryId'] as int
          : int.tryParse(json['serviceCategoryId']?.toString() ?? ''),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'duration': duration,
      'price': price,
      'currency': currency,
      'description': description,
      'location': location,
      'color': color,
      'slotInterval': slotInterval,
      'attendantsNumber': attendantsNumber,
      'isPrivate': isPrivate,
      'serviceCategoryId': serviceCategoryId,
    };
  }
}

