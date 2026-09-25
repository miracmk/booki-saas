import json
import re

categories_data = [
    {
        "category": "Güzellik / Kişisel Bakım",
        "code_prefix": "beauty",
        "items": [
            ("Kuaför", "kuafor", "Saç Kesimi, Fön, Boya & Bakım", "Koltuk", "Kuaför / Saç Tasarımcısı"),
            ("Erkek Berber", "erkek_berber", "Sakal Tıraşı, Saç Kesimi & Cilt Bakımı", "Berber Koltuğu", "Berber Ustası"),
            ("Güzellik Salonu", "guzellik_salonu", "Lazer Epilasyon, Cilt Bakımı & Manikür", "Bakım Kabini", "Uzman Estetisyen"),
            ("Nail Studio", "nail_studio", "Protez Tırnak, Kalıcı Oje & Nail Art", "Tırnak Masası", "Nail Art Uzmanı"),
            ("Manikür / Pedikür Salonu", "manikur_pedikur_salonu", "Medikal Manikür, Spa Pedikür & Parafin", "Bakım Koltuğu", "Manikürist"),
            ("Epilasyon Merkezi", "epilasyon_merkezi", "İğneli Epilasyon & Ağda Hizmetleri", "Epilasyon Kabini", "Epilasyon Uzmanı"),
            ("Lazer Epilasyon Merkezi", "lazer_epilasyon_merkezi", "Buz Lazer, Alexandrite & Diode Seansları", "Lazer Odası", "Lazer Teknisyeni"),
            ("Cilt Bakım Merkezi", "cilt_bakim_merkezi", "Hydrafacial, Kimyasal Peeling & Anti-Aging", "Cilt Bakım Odası", "Dermokozmetik Uzmanı"),
            ("Spa", "spa", "Aromaterapi, Islak Alan & Vücut Terapisi", "Spa Süiti", "Spa Terapisti"),
            ("Masaj Salonu", "masaj_salonu", "İsveç Masajı, Derin Doku & Sıcak Taş", "Masaj Odası", "Masör / Masöz"),
            ("Hamam", "hamam", "Kese-Köpük, Geleneksel Türk Hamamı & Kurna", "Kurna / Göbek Taşı", "Tellak / Natır"),
            ("Saç Ekimi Merkezi", "sac_ekimi_merkezi", "FUE, DHI Saç Ekimi & PRP Terapisi", "Operasyon Odası", "Saç Ekim Uzmanı"),
            ("Kirpik / Kaş Studio", "kirpik_kas_studio", "İpek Kirpik, Kaş Laminasyonu & Lifting", "Uygulama Koltuğu", "Lash & Brow Artisti"),
            ("Microblading Studio", "microblading_studio", "Kıl Tekniği Kaş, Dudak Renklendirme & Eyeliner", "Kalıcı Makyaj Kabini", "Microblading Uzmanı"),
            ("Makyaj Studio", "makyaj_studio", "Profesyonel Gece Makyajı & Porselen Makyaj", "Makyaj Aynası", "Make-up Artisti"),
            ("Gelinlik / Gelin Hazırlık Studio", "gelin_hazirlik_studio", "Gelin Saçı, Prova, Düğün Makyajı & Türban", "Gelin Süiti", "Gelin Saç & Makyaj Tasarımcısı"),
            ("Solaryum Merkezi", "solaryum_merkezi", "Dikey Solaryum & Ergoline Bronzlaşma", "Solaryum Kabini", "Solaryum Danışmanı"),
            ("Wellness Merkezi", "wellness_merkezi", "Detoks, Ozon Terapi & Holistik Yaşam", "Terapi Odası", "Wellness Koçu")
        ]
    },
    {
        "category": "Restoran / Yeme-İçme",
        "code_prefix": "restaurant",
        "items": [
            ("Restoran", "restoran", "Öğle / Akşam Yemeği & Özel Menü", "Masa", "Şef / Servis Personeli"),
            ("Fine Dining Restoran", "fine_dining_restoran", "Şef Tadım Menüsü & Şarap Eşleşmesi", "Loca / VIP Masa", "Executive Chef / Sommelier"),
            ("Bistro", "bistro", "Kahve, Gurme Atıştırmalık & Akşam Yemeği", "Bistro Masa", "Bistro Barista / Şef"),
            ("Kafe", "kafe", "3. Nesil Kahve, Tatlı & Kahvaltı", "Kafe Masası", "Barista"),
            ("Kahve Dükkanı", "kahve_dukkani", "Specialty Coffee, V60 & Cold Brew", "Bar Taburesi / Masa", "Head Barista"),
            ("Pub", "pub", "Fıçı İçecekler, Maç Yayını & Pub Menüsü", "Pub Masası", "Barmen / Servis"),
            ("Bar", "bar", "İmza Kokteyller & Canlı DJ Performansı", "Bar Alanı / Stand", "Mixologist"),
            ("Meyhane", "meyhane", "Fasıl, Mezeler & Geleneksel Akşam Yemeği", "Meyhane Masası", "Şef / Meyhane Müdürü"),
            ("Beach Club", "beach_club", "Şezlong, Cabana & Sahil Bar Hizmeti", "Cabana / Şezlong", "Beach Host"),
            ("Rooftop", "rooftop", "Panoramik Şehir Manzarası & Gün Batımı Kokteyli", "Teras Masası", "Rooftop Host"),
            ("Nargile Cafe", "nargile_cafe", "Özel Aromalar & Nargile Lounge Rezervasyonu", "Lounge Koltuk", "Nargile Ustası"),
            ("Kahvaltı Salonu", "kahvalti_salonu", "Serpme Köy Kahvaltısı & Brunch Rezervasyonu", "Bahçe Masası", "Kahvaltı Servis Sorumlusu"),
            ("Fast Casual Restoran", "fast_casual_restoran", "Hızlı Gurme Tabağı & Sağlıklı Kaseler", "Masa", "Mutfak Ekip Üyesi"),
            ("Pizzacı", "pizzaci", "Odun Ateşinde Napoliten Pizza Rezervasyonu", "Fırın Yanı Masa", "Pizzaiolo / Pizza Ustası"),
            ("Burger Restoranı", "burger_restorani", "Smash Burger & Craft Sos Tadımı", "Burger Masası", "Burger Ustası"),
            ("Sushi Restoranı", "sushi_restorani", "Omakase, Nigiri & Sashimi Tadım Masası", "Sushi Barı / Masa", "Sushi Şefi (Itamae)"),
            ("Pastane / Cafe", "pastane_cafe", "Özel Pasta Siparişi & Çay Saati Rezervasyonu", "Salon Masası", "Pastry Chef / Pastacı"),
            ("Özel Etkinlikli Restoran", "ozel_etkinlikli_restoran", "Canlı Müzik, Caz Gecesi & Şov Menüsü", "Sahne Önü Masa", "Etkinlik Direktörü")
        ]
    },
    {
        "category": "Spor / Fitness",
        "code_prefix": "sports",
        "items": [
            ("Fitness Salonu", "fitness_salonu", "Serbest Ağırlık, Kardiyo & Vücut Analizi", "Antrenman İstasyonu", "Fitness Eğitmeni"),
            ("Gym", "gym", "Fonksiyonel Alan & Güç Geliştirme", "Gym Alanı", "Gym Antrenörü"),
            ("Personal Training Studio", "personal_training_studio", "Birebir Özel Antrenman & Beslenme Takibi", "PT İstasyonu", "Personal Trainer"),
            ("CrossFit Box", "crossfit_box", "Grup WOD & Halter / Kondisyon Dersi", "WOD Alanı", "CrossFit Koçu"),
            ("Pilates Studio", "pilates_studio", "Mat Pilates & Hamile Pilatesi", "Stüdyo Alanı", "Pilates Eğitmeni"),
            ("Reformer Pilates Studio", "reformer_pilates_studio", "Aletli Reformer, Cadillac & Tower Seansı", "Reformer Cihazı", "Reformer Baş Eğitmeni"),
            ("Yoga Studio", "yoga_studio", "Vinyasa, Hatha & Yin Yoga Seansları", "Yoga Mat Alanı", "Yoga Master"),
            ("Dans Studio", "dans_studio", "Salsa, Bachata, Hip-hop & Bale Dersi", "Aynalı Dans Salonu", "Dans Koreografı"),
            ("Dövüş Sanatları Salonu", "dovus_sanatlari_salonu", "BJJ, Muay Thai & Krav Maga Dersi", "Tatami Minderi", "Dövüş Sanatları Antrenörü"),
            ("Boks / Kickboks Salonu", "boks_kickboks_salonu", "Boks Ringi, Torba Antrenmanı & Özel Ders", "Ring / Torba İstasyonu", "Boks Antrenörü"),
            ("Halı Saha", "hali_saha", "7v7 Suni Çim Saha Kiralama & Maç", "Halı Saha No 1", "Saha Sorumlusu"),
            ("Futbol Sahası", "futbol_sahasi", "Profesyonel Çim Saha & Akademi Çalışması", "Futbol Sahası", "Futbol Antrenörü"),
            ("Tenis Kortu", "tenis_kortu", "Toprak / Hard Kort Kiralama & Tenis Dersi", "Kort", "Tenis Pro / Antrenör"),
            ("Padel Kortu", "padel_kortu", "Cam Duvarlı Padel Kortu Kiralama & Maç", "Padel Kortu", "Padel Koçu"),
            ("Basketbol Sahası", "basketbol_sahasi", "Parke Zemin 3x3 / 5x5 Saha Kiralama", "Basketbol Sahası", "Basketbol Koçu"),
            ("Voleybol Sahası", "voleybol_sahasi", "Salon / Plaj Voleybol Sahası Kiralama", "Voleybol Kortu", "Voleybol Antrenörü"),
            ("Yüzme Havuzu", "yuzme_havuzu", "Kulvar Kiralama, Serbest Yüzme & Ders", "Kulvar", "Yüzme Antrenörü"),
            ("Spor Akademisi", "spor_akademisi", "Çoklu Branş Spor Eğitimi & Performans Testi", "Akademi Salonu", "Başantrenör"),
            ("Çocuk Spor Merkezi", "cocuk_spor_merkezi", "Cimnastik, Koordinasyon & Oyun Alanı", "Çocuk Parkuru", "Pedagojik Spor Eğitmeni"),
            ("Tırmanış / Climbing Gym", "tirmanis_climbing_gym", "Bouldering & İpli Tırmanış Duvarı", "Tırmanış Duvarı", "Tırmanış Rehberi")
        ]
    },
    {
        "category": "Sağlık / Uzmanlık",
        "code_prefix": "health",
        "items": [
            ("Diş Kliniği", "dis_klinigi", "İmplant, Zirkonyum & Diş Beyazlatma", "Diş Üniti", "Diş Hekimi"),
            ("Ağız ve Diş Sağlığı Merkezi", "agiz_ve_dis_sagligi_merkezi", "Panoramik Röntgen, Kanal Tedavisi & Pedodonti", "Poliklinik Üniti", "Uzman Diş Hekimi"),
            ("Doktor Muayenehanesi", "doktor_muayenehanesi", "Genel Muayene, Konsültasyon & Raporlama", "Muayene Odası", "Uzman Doktor"),
            ("Özel Klinik", "ozel_klinik", "Check-up, Teşhis & Tedavi Planı", "Klinik Odası", "Klinik Direktörü Doktor"),
            ("Poliklinik", "poliklinik", "Branş Muayeneleri & Laboratuvar Tetkiki", "Poliklinik Odası", "Poliklinik Hekimi"),
            ("Psikolog", "psikolog", "Bireysel Terapi, BDT & EMDR Seansı", "Görüşme Odası", "Uzman Klinik Psikolog"),
            ("Psikolojik Danışmanlık Merkezi", "psikolojik_danismanlik_merkezi", "Çift Terapisi, Aile Danışmanlığı & Çocuk Değerlendirme", "Terapi Salonu", "Aile Danışmanı / Psikolog"),
            ("Diyetisyen", "diyetisyen", "İnbody Analizi, Kilo Yönetimi & Sporcu Beslenmesi", "Beslenme Odası", "Uzman Diyetisyen"),
            ("Fizyoterapist", "fizyoterapist", "Manuel Terapi, Kuru İğneleme & Egzersiz", "Fizyoterapi Kabini", "Uzman Fizyoterapist"),
            ("Fizik Tedavi Merkezi", "fizik_tedavi_merkezi", "Robotik Rehabilitasyon & Elektroterapi", "Tedavi Odası", "Fizik Tedavi Uzmanı"),
            ("Osteopati / Manuel Terapi", "osteopati_manuel_terapi", "Kranyosakral Terapi & Omurga Manipülasyonu", "Osteopati Masası", "Osteopat"),
            ("Chiropractor / Kayropraktik", "chiropractor_kayropraktik", "Omurga Hizalama & Subluksasyon Düzeltme", "Kayropraktik Masası", "Kayropraktik Uzmanı"),
            ("Veteriner Kliniği", "veteriner_klinigi", "Aşı, Mikroçip, Genel Muayene & Parazit Tedavisi", "Muayene Masası", "Veteriner Hekim"),
            ("Veteriner Hastanesi", "veteriner_hastanesi", "7/24 Acil, Cerrahi Operasyon & Yoğun Bakım", "Cerrahi Ünite", "Başhekim Veteriner"),
            ("Göz Kliniği", "goz_klinigi", "Göz Numarası Ölçümü, Lazer Uygunluk & Göz Dibi", "Göz Muayene Odası", "Göz Hastalıkları Uzmanı"),
            ("Estetik Kliniği", "estetik_klinigi", "Dolgu, Botoks, Mezoterapi & İp Askı", "Estetik Uygulama Odası", "Medikal Estetik Hekimi"),
            ("Dermatoloji Kliniği", "dermatoloji_klinigi", "Ben Taraması, Akne Tedavisi & Alerji Testi", "Dermatoloji Muayenehanesi", "Dermatolog Doktor"),
            ("Medikal Estetik Merkezi", "medikal_estetik_merkezi", "Gençlik Aşısı, Leke Protokolü & Karboksi", "Medikal Kabin", "Medikal Estetisyen Hekim"),
            ("Konuşma / Dil Terapisi Merkezi", "konusma_dil_terapisi_merkezi", "Kekemelik, Artikülasyon & Afazi Terapisi", "Terapi Kabini", "Dil ve Konuşma Terapisti"),
            ("Ergoterapi Merkezi", "ergoterapi_merkezi", "Duyu Bütünleme & İnce Motor Becerileri", "Duyu Odası", "Uzman Ergoterapist")
        ]
    },
    {
        "category": "Otomotiv",
        "code_prefix": "automotive",
        "items": [
            ("Oto Yıkama", "oto_yikama", "Köpüklü İç-Dış Yıkama, Jant Temizliği & Hızlı Cila", "Yıkama Peronu", "Yıkama Personeli"),
            ("Detailing Studio", "detailing_studio", "Çok Aşamalı Polisaj, Seramik & Deri Koruma", "Detailing Kabini", "Detailing Master"),
            ("Oto Kuaför", "oto_kuafor", "Koltuk Yıkama, Tavan Temizliği & Ozon Dezenfeksiyon", "Kuaför İstasyonu", "Oto Kuaför Ustası"),
            ("Seramik Kaplama Merkezi", "seramik_kaplama_merkezi", "9H Nano Seramik & Grafen Boya Koruması", "Tozsuz Seramik Odası", "Seramik Kaplama Uzmanı"),
            ("PPF Merkezi", "ppf_merkezi", "Şeffaf TPU Boya Koruma Filmi (PPF) Kaplama", "PPF Uygulama Peronu", "PPF Montaj Ustası"),
            ("Boya Koruma Merkezi", "boya_koruma_merkezi", "Hale Giderme, Boya Düzeltme & Koruyucu Cila", "Boya Koruma İstasyonu", "Boya Koruma Teknisyeni"),
            ("Oto Ekspertiz", "oto_ekspertiz", "101 Nokta DVI Raporu, Dyno Test & Kaporta Kontrol", "Ekspertiz Hattı", "Sertifikalı Ekspertiz Ustası"),
            ("Oto Servis", "oto_servis", "Periyodik Bakım, Yağ & Filtre Değişimi, Fren Testi", "Mekanik Lift", "Servis Başteknisyeni"),
            ("Yetkili Servis", "yetkili_servis", "Orijinal Yedek Parça & Garantili Bakım Servisi", "Servis Peronu", "Yetkili Servis Danışmanı"),
            ("Özel Servis", "ozel_servis", "Alman / Japon Araç Grubu Özel Bakım & Arıza Teşhis", "Özel Servis Lifti", "Mekanik Arıza Uzmanı"),
            ("Lastik Servisi", "lastik_servisi", "Lastik Değişimi, Balans, Tamir & Lastik Oteli", "Lastik Sökme İstasyonu", "Lastik Teknisyeni"),
            ("Jant Servisi", "jant_servisi", "Jant Düzeltme, CNC Boyama & Elmas Kesim", "Jant Torna Alanı", "Jant Onarım Ustası"),
            ("Rot-Balans Merkezi", "rot_balans_merkezi", "3D Kameralı Lazer Rot Ayarı & Tekerlek Balansı", "3D Rot Kanalı", "Rot-Balans Ustası"),
            ("Oto Klima Servisi", "oto_klima_servisi", "Klima Gazı Dolumu, Kaçak Tespiti & Evaporatör Temizliği", "Klima Cihazı İstasyonu", "Klima Teknisyeni"),
            ("Elektrikli Araç Servisi", "elektrikli_arac_servisi", "Batarya Sağlık Testi (SoH), Şarj & Yüksek Voltaj Bakımı", "EV İzolasyonlu Lift", "Yüksek Voltaj (EV) Uzmanı"),
            ("Kaporta / Boya", "kaporta_boya", "Göçük Düzeltme, Fırın Boya & Mini Onarım", "Boya Fırını / Düzeltme Masası", "Kaporta & Boya Ustası"),
            ("Cam Filmi Merkezi", "cam_filmi_merkezi", "Isı Yalıtımlı Güneş Koruma Cam Filmi Uygulaması", "Cam Filmi Atölyesi", "Cam Filmi Uygulayıcısı"),
            ("Modifiye / Tuning", "modifiye_tuning", "Yazılım (Chip Tuning), Egzoz & Body Kit Montajı", "Dyno / Tuning Alanı", "Yazılım & Tuning Uzmanı"),
            ("Motosiklet Servisi", "motosiklet_servisi", "Motosiklet Zincir, Fren, Yağ & Süspansiyon Bakımı", "Motosiklet Lifti", "Motosiklet Mekanisyeni"),
            ("Karavan Servisi", "karavan_servisi", "Güneş Paneli, Webasto, Su & İzolasyon Bakımı", "Karavan Geniş Peronu", "Karavan Donanım Ustası")
        ]
    },
    {
        "category": "Deneyim / Eğlence / Aktivite",
        "code_prefix": "experience",
        "items": [
            ("Escape Room", "escape_room", "Kaçış Oyunu Rezervasyonu (60 Dakika)", "Tematik Kaçış Odası", "Oyun Yöneticisi (Game Master)"),
            ("VR Gaming Center", "vr_gaming_center", "Sanal Gerçeklik Simülatörü & Çok Oyunculu Seans", "VR Platformu", "VR Operatörü"),
            ("Arcade", "arcade", "Retro Oyun Jetonu & Eğlence Masası Rezervasyonu", "Arcade Alanı", "Arcade Görevlisi"),
            ("Bowling", "bowling", "Kulvar Kiralama, Ayakkabı & Puanlı Maç", "Bowling Kulvarı", "Bowling Hakemi / Sorumlusu"),
            ("Bilardo Salonu", "bilardo_salonu", "3 Bant & Amerikan Bilardo Masası Kiralama", "Bilardo Masası", "Salon Hakemi"),
            ("PlayStation / Gaming Cafe", "playstation_gaming_cafe", "PS5 VIP Oda, EA FC & Yarış Simülatörü", "PS5 VIP Odası", "Gaming Cafe Sorumlusu"),
            ("Çocuk Oyun Merkezi", "cocuk_oyun_merkezi", "Softplay Alanı, Top Havuzu & Doğum Günü Kutlaması", "Oyun Alanı", "Oyun Ablası / Abisi"),
            ("Trambolin Parkı", "trambolin_parki", "Serbest Zıplama & Sünger Havuzu Seansı", "Trambolin Alanı", "Parkur Güvenlik Eğitmeni"),
            ("Tırmanış Merkezi", "tirmanis_merkezi", "Bouldering & Rehberli Tırmanış Eğitimi", "Tırmanış Parkuru", "Tırmanış Eğitmeni"),
            ("Paintball", "paintball", "Takım Savaşı & Senaryo Oyunu Rezervasyonu", "Paintball Sahası", "Saha Hakemi"),
            ("Airsoft", "airsoft", "Taktik CQB Airsoft Karşılaşması & Ekipman Kiralama", "Taktik CQB Alanı", "Airsoft Hakemi"),
            ("Go-Kart", "go_kart", "10 Dakikalık Yarış Seansı & Zaman Turları", "Yarış Pisti", "Pist Şefi"),
            ("Atölye Merkezi", "atolye_merkezi", "Yaratıcı Workshop Seansı & Malzeme Paketi", "Çalışma Tezgahı", "Atölye Kolaylaştırıcı"),
            ("Seramik Atölyesi", "seramik_atolyesi", "Çömlek Torna Seansı, Sırlama & Pişirim", "Seramik Tornası", "Seramik Sanatçısı"),
            ("Resim Atölyesi", "resim_atolyesi", "Tuval Üzerine Akrilik & Yağlıboya Atölyesi", "Şövale İstasyonu", "Ressam / Eğitmen"),
            ("Ahşap Atölyesi", "ahsap_atolyesi", "Marangozluk & Ahşap Oyma Eğitimi", "Marangoz Tezgahı", "Ahşap Ustası"),
            ("Müzik / Enstrüman Atölyesi", "muzik_enstruman_atolyesi", "Gitar, Bateri & Piyano Tanıtım Seansı", "Akustik Prova Odası", "Müzik Eğitmeni"),
            ("Fotoğraf Studio", "fotograf_studio", "Stüdyo Işık Kiralama & Portre Çekimi", "Fotoğraf Fon Alanı", "Stüdyo Fotoğrafçısı"),
            ("Etkinlik / Workshop Mekanı", "etkinlik_workshop_mekani", "Seminer, Lansman & Şirket İçi Etkinlik", "Etkinlik Salonu", "Etkinlik Koordinatörü"),
            ("Macera / Outdoor Aktivite Merkezi", "macera_outdoor_aktivite_merkezi", "Zipline, İp Parkuru & Doğa Yürüyüşü", "Macera İstasyonu", "Outdoor Lideri")
        ]
    },
    {
        "category": "Konaklama",
        "code_prefix": "hotel",
        "items": [
            ("Butik Otel", "butik_otel", "Tarihi Konak Odası & Gurme Kahvaltı", "Deluxe Butik Oda", "Ön Büro Müdürü"),
            ("Şehir Oteli", "sehir_oteli", "İş Seyahati Odası & Toplantı Salonu", "Standart Oda", "Resepsiyonist"),
            ("Apart Otel", "apart_otel", "Mutfaklı 1+1 Daire & Uzun Dönem Konaklama", "Apart Daire", "Apart Sorumlusu"),
            ("Pansiyon", "pansiyon", "Ekonomik Oda & Samimi Aile İşletmesi", "Pansiyon Odası", "Ev Sahibi / Pansiyoncu"),
            ("Hostel", "hostel", "Yataklı Paylaşımlı Oda (Dorm) & Sosyal Alan", "Dorm Odası / Yatak", "Hostel Görevlisi"),
            ("Bungalov", "bungalov", "Müstakil Ahşap Bungalov, Jakuzi & Şömine", "Jakuzili Bungalov", "Tesis Müdürü"),
            ("Villa Kiralama", "villa_kiralama", "Özel Havuzlu Lüks Müstakil Villa", "Villa 1", "Villa Danışmanı"),
            ("Glamping", "glamping", "Lüks Kubbe Çadır (Dome) & Doğa İçinde Konfor", "Lüks Dome Çadır", "Glamping Hostu"),
            ("Kamp Alanı", "kamp_alani", "Çadır / Karavan Parseli, Elektrik & Ortak Alan", "Kamp Parseli", "Kamp Müdürü"),
            ("Dağ Evi", "dag_evi", "Uludağ Eteklerinde Şömineli Dağ Kulübesi", "Dağ Evi Süiti", "Dağ Evi Yöneticisi"),
            ("Tiny House", "tiny_house", "Minimalist Yaşam Deneyimi & Müstakil Bahçe", "Tiny House Bahçeli", "Konaklama Asistanı"),
            ("Tatil Köyü", "tatil_koyu", "Her Şey Dahil Aile Konaklaması & Aqua Park", "Aile Odası", "Müşteri İlişkileri Temsilcisi"),
            ("Termal Otel", "termal_otel", "Şifalı Kaplıca Havuzu, Termal Küvet & Çamur Banyosu", "Termal Oda", "Termal Tesis Şefi"),
            ("Spa Hotel", "spa_hotel", "Spa Paketi Dahil Konaklama & Masaj", "Spa Süiti", "Spa Otel Danışmanı"),
            ("Kısa Dönem Kiralama", "kisa_donem_kiralama", "Airbnb Tarzı Mobilyalı Daire Rezervasyonu", "Şehir Dairesi", "Mülk Yöneticisi")
        ]
    },
    {
        "category": "Eğitim / Kurs / Birebir Ders",
        "code_prefix": "education",
        "items": [
            ("Dil Kursu", "dil_kursu", "İngilizce, Almanca Birebir & Grup Dersi", "Derslik", "Dil Eğitmeni"),
            ("Özel Ders Merkezi", "ozel_ders_merkezi", "Matematik, Fizik & Birebir Takviye", "Birebir Çalışma Masası", "Branş Öğretmeni"),
            ("Müzik Kursu", "muzik_kursu", "Piyano, Gitar, Keman & Şan Dersi", "Ses Yalıtımlı Müzik Odası", "Müzik Öğretmeni"),
            ("Dans Kursu", "dans_kursu", "Tango, Salsa, Hip-Hop & Sosyal Latin", "Dans Parkesi", "Dans Eğitmeni"),
            ("Resim Kursu", "resim_kursu", "Karakalem, Yağlı Boya & Güzel Sanatlar Hazırlık", "Sanat Atölyesi", "Görsel Sanatlar Öğretmeni"),
            ("Kodlama / Yazılım Kursu", "kodlama_yazilim_kursu", "Python, Web Geliştirme & Robotik Kodlama", "Bilgisayar Laboratuvarı", "Yazılım Mentoru"),
            ("Çocuk Eğitim Merkezi", "cocuk_egitim_merkezi", "Montessori, Akıl Oyunları & Erken Gelişim", "Aktivite Odası", "Çocuk Gelişimi Uzmanı"),
            ("Sürücü Kursu", "surucu_kursu", "B Sınıfı Direksiyon Eğitimi & Simülatör", "Eğitim Aracı", "Usta Öğretici"),
            ("Ehliyet Eğitim Merkezi", "ehliyet_egitim_merkezi", "A1/A2 Motosiklet & Ağır Vasıta Eğitimi", "Pist Aracı", "Direksiyon Usta Öğreticisi"),
            ("Mesleki Eğitim Merkezi", "mesleki_egitim_merkezi", "CNC, Kaynak, Aşçılık Sertifika Programı", "Uygulama Atölyesi", "Meslek Eğitmeni"),
            ("Sınav Hazırlık Merkezi", "sinav_hazirlik_merkezi", "YKS, LGS, KPSS Deneme Sınavı & Koçluk", "Sınav Salonu", "Rehber Öğretmen"),
            ("Etüt Merkezi", "etut_merkezi", "Sessiz Kütüphane & Ödev Takip Seansı", "Etüt Masası", "Etüt Gözetmeni"),
            ("Online Eğitim Merkezi", "online_egitim_merkezi", "Canlı Birebir Uzaktan Ders & Ekran Paylaşımı", "Sanal Sınıf", "Online Danışman Öğretmen"),
            ("Koçluk / Mentorluk", "kocluk_mentorluk", "Kariyer & Yönetici Koçluğu Görüşmesi", "Birebir Koçluk Odası", "Profesyonel Koç (ICF)"),
            ("Workshop / Seminer Merkezi", "workshop_seminer_merkezi", "Kurumsal Eğitim & Yetkinlik Geliştirme", "Seminer Salonu", "Kurumsal Eğitmen")
        ]
    },
    {
        "category": "Profesyonel Hizmet / Görüşme Bazlı İşletmeler",
        "code_prefix": "professional",
        "items": [
            ("Avukatlık Bürosu", "avukatlik_burosu", "Hukuki Danışmanlık, Dava & Sözleşme İncelemesi", "Toplantı Odası", "Avukat"),
            ("Mali Müşavir", "mali_musavir", "Vergi Planlaması, Şirket Kuruluşu & Bordro", "Görüşme Masası", "SMMM (Mali Müşavir)"),
            ("Muhasebe Ofisi", "muhasebe_ofisi", "Finansal Tablolar, KDV İadesi & Defter Tutma", "Muhasebe Çalışma Alanı", "Mali Denetçi"),
            ("Danışmanlık Şirketi", "danismanlik_sirketi", "Yönetim Danışmanlığı, KVKK & Strateji", "Konferans Masası", "Kıdemli Yönetim Danışmanı"),
            ("Gayrimenkul Danışmanlığı", "gayrimenkul_danismanligi", "Ekspertiz, Portföy Sunumu & Tapu Danışmanlığı", "Müşteri Kabul Odası", "Gayrimenkul Danışmanı"),
            ("Sigorta Acentesi", "sigorta_acentesi", "Kasko, Trafik, DASK & Özel Sağlık Teklifi", "Acente Masası", "Sigorta Uzmanı"),
            ("Finansal Danışman", "finansal_danisman", "Yatırım Stratejisi, Bütçe & Portföy Yönetimi", "Danışmanlık Odası", "Finansal Danışman"),
            ("İnsan Kaynakları Danışmanlığı", "insan_kaynaklari_danismanligi", "Mülakat, İşe Alım & Bordro Outsourcing", "Mülakat Odası", "İK Direktörü"),
            ("Kariyer Danışmanlığı", "kariyer_danismanligi", "CV İnceleme, Kariyer Planlama & Simülasyon", "Görüşme Odası", "Kariyer Danışmanı"),
            ("Mimarlık Ofisi", "mimarlik_ofisi", "Ruhsat Projesi, Konsept Tasarım & Şantiye Takibi", "Proje Çizim Masası", "Mimar"),
            ("İç Mimarlık Ofisi", "ic_mimarlik_ofisi", "3D Render, Mahal Listesi & Dekorasyon Uygulama", "İç Mimari Sunum Odası", "İç Mimar"),
            ("Fotoğrafçı", "fotografci", "Ürün Çekimi, Katalog & Profesyonel Portre", "Çekim Stüdyosu", "Fotoğraf Sanatçısı"),
            ("Düğün Fotoğrafçısı", "dugun_fotografcisi", "Dış Çekim, Düğün Hikayesi & Albüm Görüşmesi", "Sunum Salonu", "Düğün Fotoğrafçısı"),
            ("Reklam / Kreatif Ajans", "reklam_kreatif_ajans", "Marka Kimliği, Kampanya & Prodüksiyon", "Kreatif Toplantı Masası", "Kreatif Direktör"),
            ("Dijital Pazarlama Ajansı", "dijital_pazarlama_ajansi", "Google Ads, Meta Reklamları, SEO & Büyüme", "Ajans Masası", "Dijital Pazarlama Uzmanı")
        ]
    }
]

tr_map = {
    "ş": "s", "Ş": "s", "ç": "c", "Ç": "c", "ğ": "g", "Ğ": "g",
    "ü": "u", "Ü": "u", "ö": "o", "Ö": "o", "ı": "i", "İ": "i",
    "â": "a", "Â": "a", "/": "-", "&": "ve"
}

def to_slug(text):
    for k, v in tr_map.items():
        text = text.replace(k, v)
    text = re.sub(r'[^a-zA-Z0-9\-]+', '-', text.lower())
    return re.sub(r'-+', '-', text).strip('-')

all_businesses = []
for cat_obj in categories_data:
    cat_name = cat_obj["category"]
    code_prefix = cat_obj["code_prefix"]
    for item in cat_obj["items"]:
        b_type, slug_suffix, desc, station_prefix, staff_title = item
        slug = f"demo-{to_slug(b_type)}"
        db_name = f"ki_tenant_demo_{slug_suffix}"
        all_businesses.append({
            "category": cat_name,
            "business_type": b_type,
            "company_name": f"{b_type} Demo",
            "subdomain": slug,
            "db_name": db_name,
            "description": desc,
            "station_prefix": station_prefix,
            "staff_title": staff_title,
            "code_prefix": code_prefix
        })

print(f"Total businesses configured: {len(all_businesses)}")
with open("/tmp/businesses_161.json", "w", encoding="utf-8") as f:
    json.dump(all_businesses, f, ensure_ascii=False, indent=2)
print("Saved to /tmp/businesses_161.json")
