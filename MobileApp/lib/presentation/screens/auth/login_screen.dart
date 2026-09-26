import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/tenant_model.dart';
import '../../../providers/auth_provider.dart';
import 'register_screen.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _pageController = PageController();
  int _currentStep = 0; // 0: İşletme Kodu, 1: Giriş Bilgileri

  // Form Keys & Controllers
  final _tenantFormKey = GlobalKey<FormState>();
  final _loginFormKey = GlobalKey<FormState>();

  final _tenantController = TextEditingController();
  final _identifierController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _obscurePassword = true;
  bool _isLoadingTenants = false;
  List<TenantModel> _suggestedTenants = [];
  String _selectedTenantCode = '';
  String _selectedTenantName = '';

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  Future<void> _loadInitialData() async {
    final storage = ref.read(storageServiceProvider);
    final lastCode = storage.getLastBusinessCode();
    final lastName = storage.getLastBusinessName();

    if (lastCode != null && lastCode.isNotEmpty) {
      _tenantController.text = lastCode;
      _selectedTenantCode = lastCode;
      _selectedTenantName = lastName ?? lastCode;
    }

    // Aktif işletmeleri arka planda yükle
    setState(() => _isLoadingTenants = true);
    try {
      final tenants = await ref.read(authRepositoryProvider).getTenants();
      if (mounted) {
        setState(() {
          _suggestedTenants = tenants;
          _isLoadingTenants = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() => _isLoadingTenants = false);
      }
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    _tenantController.dispose();
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  String _sanitizeSlug(String raw) {
    var val = raw.trim().toLowerCase();
    val = val.replaceAll(RegExp(r'^https?://', caseSensitive: false), '');
    val = val.replaceAll(RegExp(r'/.*$'), '');
    val = val.replaceAll(RegExp(r':\d+$'), '');
    val = val.replaceAll(RegExp(r'[-.]bookiapp\.kibusiness\.co$', caseSensitive: false), '');
    val = val.replaceAll(RegExp(r'^@'), '');
    return val;
  }

  void _proceedToCredentials({String? customCode, String? customName}) {
    final rawCode = customCode ?? _tenantController.text;
    final cleanCode = _sanitizeSlug(rawCode);

    if (cleanCode.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen geçerli bir işletme kodu girin.')),
      );
      return;
    }

    String displayName = customName ?? cleanCode;
    // Varsa listeden işletme adını eşleştir
    final matched = _suggestedTenants.where(
      (t) => t.subdomain.toLowerCase() == cleanCode.toLowerCase(),
    );
    if (matched.isNotEmpty) {
      displayName = matched.first.companyName;
    }

    setState(() {
      _selectedTenantCode = cleanCode;
      _selectedTenantName = displayName;
      _tenantController.text = cleanCode;
      _currentStep = 1;
    });

    // Son kullanılan işletmeyi yerel hafızaya kaydet
    final storage = ref.read(storageServiceProvider);
    storage.saveLastBusinessCode(cleanCode, name: displayName);

    _pageController.animateToPage(
      1,
      duration: const Duration(milliseconds: 320),
      curve: Curves.easeInOutCubic,
    );
  }

  void _backToTenantStep() {
    setState(() => _currentStep = 0);
    _pageController.animateToPage(
      0,
      duration: const Duration(milliseconds: 320),
      curve: Curves.easeInOutCubic,
    );
  }

  Future<void> _submitLogin() async {
    if (!_loginFormKey.currentState!.validate()) return;

    final identifier = _identifierController.text.trim();
    final password = _passwordController.text;

    await ref.read(authProvider.notifier).login(
          identifier: identifier,
          password: password,
          tenantSubdomain: _selectedTenantCode.isNotEmpty ? _selectedTenantCode : null,
        );
  }

  void _showServerSettingsDialog() {
    final storage = ref.read(storageServiceProvider);
    final currentUrl = storage.getBaseUrl() ?? ApiConstants.defaultProductionUrl;
    final urlController = TextEditingController(text: currentUrl);

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Sunucu URL Ayarı'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Canlı SaaS: https://bookiapp.kibusiness.co\nAndroid Emülatör: http://10.0.2.2',
              style: TextStyle(fontSize: 12, color: AppTheme.textSecondaryLight),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: urlController,
              decoration: const InputDecoration(
                labelText: 'API Base URL',
                hintText: 'https://...',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('İptal'),
          ),
          ElevatedButton(
            onPressed: () {
              final newUrl = urlController.text.trim();
              if (newUrl.isNotEmpty) {
                ref.read(apiClientProvider).updateBaseUrl(newUrl);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Sunucu URL güncellendi: $newUrl')),
                );
              }
              Navigator.of(ctx).pop();
            },
            child: const Text('Kaydet'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return PopScope(
      canPop: _currentStep == 0,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && _currentStep == 1) {
          _backToTenantStep();
        }
      },
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: _currentStep == 1
              ? IconButton(
                  icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
                  tooltip: 'İşletme Seçimine Dön',
                  onPressed: _backToTenantStep,
                )
              : null,
          actions: [
            IconButton(
              icon: const Icon(Icons.settings_outlined),
              tooltip: 'Sunucu Ayarları',
              onPressed: _showServerSettingsDialog,
            ),
          ],
        ),
        body: SafeArea(
          child: Center(
            child: PageView(
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                _buildStep1BusinessCode(context),
                _buildStep2Credentials(context, authState),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // STEP 1: İşletme Kodu Belirleme
  Widget _buildStep1BusinessCode(BuildContext context) {
    final rawInput = _tenantController.text.trim();
    final slugPreview = _sanitizeSlug(rawInput);

    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Form(
        key: _tenantFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Logo
            Center(
              child: Container(
                width: 76,
                height: 76,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(22),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primaryColor.withValues(alpha: 0.18),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Image.asset(
                  'assets/images/logo_icon.png',
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stackTrace) => const Icon(
                    Icons.calendar_month_rounded,
                    size: 38,
                    color: AppTheme.primaryColor,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Logo Wordmark
            Center(
              child: Image.asset(
                'assets/images/logo.png',
                height: 36,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => Text(
                  'BooKi',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.primaryColor,
                      ),
                ),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Online Randevu ve İşletme Yönetimi',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: AppTheme.textSecondaryLight,
                  ),
            ),
            const SizedBox(height: 32),

            // Portal Başlık Kartı
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppTheme.borderLight),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: AppTheme.primaryColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(
                          Icons.store_mall_directory_rounded,
                          color: AppTheme.primaryColor,
                          size: 22,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'İşletme Girişi',
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w800,
                                    color: AppTheme.darkNavy,
                                  ),
                            ),
                            const SizedBox(height: 2),
                            const Text(
                              'İşletmenizin kodunu veya adını girin',
                              style: TextStyle(
                                fontSize: 12,
                                color: AppTheme.textSecondaryLight,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),

                  // İşletme Kodu Giriş Alanı
                  TextFormField(
                    controller: _tenantController,
                    onChanged: (_) => setState(() {}),
                    textInputAction: TextInputAction.go,
                    onFieldSubmitted: (val) => _proceedToCredentials(),
                    decoration: InputDecoration(
                      labelText: 'İşletme Kodu (Subdomain)',
                      hintText: 'ornek-isletme',
                      prefixIcon: const Icon(Icons.alternate_email_rounded),
                      suffixIcon: _tenantController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _tenantController.clear();
                                setState(() {});
                              },
                            )
                          : null,
                    ),
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return 'Lütfen işletme kodunu girin';
                      }
                      return null;
                    },
                  ),

                  // Canlı URL Önizleme
                  const SizedBox(height: 10),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceLight,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppTheme.borderLight),
                    ),
                    child: Text(
                      slugPreview.isNotEmpty
                          ? 'Giriş Adresi: https://$slugPreview-bookiapp.kibusiness.co'
                          : 'Giriş Adresi: https://...-bookiapp.kibusiness.co',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: slugPreview.isNotEmpty
                            ? AppTheme.primaryColor
                            : AppTheme.textSecondaryLight,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Devam Et Butonu
                  ElevatedButton(
                    onPressed: () => _proceedToCredentials(),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(50),
                    ),
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text('Devam Et'),
                        SizedBox(width: 8),
                        Icon(Icons.arrow_forward_rounded, size: 18),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Önerilen / Kayıtlı İşletmeler
            if (_isLoadingTenants) ...[
              const SizedBox(height: 20),
              const Center(
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            ] else if (_suggestedTenants.isNotEmpty) ...[
              const SizedBox(height: 24),
              Row(
                children: [
                  const Icon(Icons.explore_outlined,
                      size: 16, color: AppTheme.textSecondaryLight),
                  const SizedBox(width: 6),
                  Text(
                    'Kayıtlı İşletmeler',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          fontWeight: FontWeight.w700,
                          color: AppTheme.textSecondaryLight,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: _suggestedTenants.take(8).map((t) {
                  return ActionChip(
                    avatar: const Icon(Icons.storefront_rounded, size: 15),
                    label: Text(t.companyName.isNotEmpty ? t.companyName : t.subdomain),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: AppTheme.borderLight),
                    labelStyle: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppTheme.darkNavy,
                    ),
                    onPressed: () {
                      _proceedToCredentials(
                        customCode: t.subdomain,
                        customName: t.companyName,
                      );
                    },
                  );
                }).toList(),
              ),
            ],

            const SizedBox(height: 32),
            Center(
              child: Text(
                'BooKi SaaS • kibusiness.co',
                style: TextStyle(
                  fontSize: 12,
                  color: AppTheme.textSecondaryLight.withValues(alpha: 0.7),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // STEP 2: Giriş Bilgileri (Müşteri veya İşletme Admin / Personel)
  Widget _buildStep2Credentials(BuildContext context, AuthState authState) {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Form(
        key: _loginFormKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Aktif İşletme Kartı & Değiştir Butonu
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppTheme.primaryColor.withValues(alpha: 0.08),
                    AppTheme.secondaryColor.withValues(alpha: 0.08),
                  ],
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: AppTheme.primaryColor.withValues(alpha: 0.25),
                ),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(
                      Icons.storefront_rounded,
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _selectedTenantName.isNotEmpty
                              ? _selectedTenantName
                              : _selectedTenantCode,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            color: AppTheme.darkNavy,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          '$_selectedTenantCode.bookiapp.kibusiness.co',
                          style: const TextStyle(
                            fontSize: 11,
                            color: AppTheme.primaryColor,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  TextButton.icon(
                    onPressed: _backToTenantStep,
                    icon: const Icon(Icons.swap_horiz_rounded, size: 16),
                    label: const Text('Değiştir'),
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      visualDensity: VisualDensity.compact,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),

            // Başlık
            Text(
              'Giriş Yap',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppTheme.darkNavy,
                  ),
            ),
            const SizedBox(height: 4),
            Text(
              'Müşteri, işletme yetkilisi veya personel hesabınızla giriş yapın.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: AppTheme.textSecondaryLight,
                  ),
            ),
            const SizedBox(height: 20),

            // Bilgilendirme Rozeti (Otomatik Rol)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: AppTheme.surfaceLight,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppTheme.borderLight),
              ),
              child: const Row(
                children: [
                  Icon(Icons.auto_awesome_rounded,
                      size: 16, color: AppTheme.secondaryColor),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Müşteri ve Personel hesapları otomatik olarak ilgili panele yönlendirilir.',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w500,
                        color: AppTheme.textSecondaryLight,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Hata Mesajı
            if (authState.errorMessage != null) ...[
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppTheme.accentDanger.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: AppTheme.accentDanger.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline_rounded,
                        color: AppTheme.accentDanger, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        authState.errorMessage!,
                        style: const TextStyle(
                          color: AppTheme.accentDanger,
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
            ],

            // E-posta / Kullanıcı Adı
            TextFormField(
              controller: _identifierController,
              decoration: const InputDecoration(
                labelText: 'E-posta veya Kullanıcı Adı',
                prefixIcon: Icon(Icons.person_outline_rounded),
              ),
              textInputAction: TextInputAction.next,
              validator: (val) =>
                  val == null || val.trim().isEmpty ? 'Gerekli alan' : null,
            ),
            const SizedBox(height: 16),

            // Şifre
            TextFormField(
              controller: _passwordController,
              obscureText: _obscurePassword,
              decoration: InputDecoration(
                labelText: 'Şifre',
                prefixIcon: const Icon(Icons.lock_outline_rounded),
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePassword
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined,
                  ),
                  onPressed: () {
                    setState(() {
                      _obscurePassword = !_obscurePassword;
                    });
                  },
                ),
              ),
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _submitLogin(),
              validator: (val) =>
                  val == null || val.isEmpty ? 'Şifre gerekli' : null,
            ),
            const SizedBox(height: 24),

            // Giriş Yap Butonu
            ElevatedButton(
              onPressed: authState.isLoading ? null : _submitLogin,
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(52),
              ),
              child: authState.isLoading
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.5,
                        color: Colors.white,
                      ),
                    )
                  : const Text(
                      'Giriş Yap',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                    ),
            ),
            const SizedBox(height: 20),

            // Yeni Kayıt Linki
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text(
                  'Bu işletmede hesabınız yok mu? ',
                  style: TextStyle(color: AppTheme.textSecondaryLight),
                ),
                GestureDetector(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => const RegisterScreen(),
                      ),
                    );
                  },
                  child: const Text(
                    'Kayıt Olun',
                    style: TextStyle(
                      color: AppTheme.primaryColor,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
