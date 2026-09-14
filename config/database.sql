-- ============================================================
-- Haber Portali - Veritabani Semasi
-- phpMyAdmin veya mysql CLI ile calistirin:
--   mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS haber_portali CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE haber_portali;

-- --------------------------------------------------------
-- Yoneticiler
-- --------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    role ENUM('super_admin','editor','author') NOT NULL DEFAULT 'author',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Varsayilan sifre: Admin123! (asagida bcrypt hash var, ilk girişten sonra degistirin)
INSERT INTO admins (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$12$UAJ53Sv4qb7YBuCV3xOdiemMoXiJtP/NXskS3OVnmCPy.xYDtuYhu', 'Site Yoneticisi', 'admin@example.com', 'super_admin');

-- --------------------------------------------------------
-- Kategoriler
-- --------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    parent_id INT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, sort_order) VALUES
('Gundem', 'gundem', 1),
('Dunya', 'dunya', 2),
('Ekonomi', 'ekonomi', 3),
('Spor', 'spor', 4),
('Teknoloji', 'teknoloji', 5),
('Magazin', 'magazin', 6),
('Saglik', 'saglik', 7),
('Yasam', 'yasam', 8);

-- --------------------------------------------------------
-- Etiketler
-- --------------------------------------------------------
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Haberler
-- --------------------------------------------------------
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    summary VARCHAR(500) DEFAULT NULL,
    content MEDIUMTEXT NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    category_id INT NOT NULL,
    author_id INT NOT NULL,
    status ENUM('draft','published','pending') NOT NULL DEFAULT 'draft',
    is_breaking TINYINT(1) NOT NULL DEFAULT 0,
    is_slider TINYINT(1) NOT NULL DEFAULT 0,
    slider_order INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT NOT NULL DEFAULT 0,
    published_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (author_id) REFERENCES admins(id),
    INDEX idx_status_published (status, published_at),
    INDEX idx_slider (is_slider, slider_order)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Demo haberler (orijinal ornek icerik - siteyi bos acmamak icin)
-- --------------------------------------------------------
INSERT INTO news (title, slug, summary, content, category_id, author_id, status, is_breaking, is_slider, slider_order, is_featured, views, published_at) VALUES
('Meclis''te yeni yasa teklifi görüşülüyor', 'mecliste-yeni-yasa-teklifi-gorusuluyor', 'Genel Kurul''da görüşülmeye başlanan teklif, birçok alanda düzenleme öngörüyor.', '<p>TBMM Genel Kurulu''nda görüşülmeye başlanan yasa teklifi, çeşitli sektörleri ilgilendiren düzenlemeler içeriyor. Komisyon aşamasından geçen teklifin önümüzdeki günlerde oylanması bekleniyor.</p><p>Yetkililer, teklifin yürürlüğe girmesi halinde vatandaşların günlük hayatını doğrudan etkileyecek yenilikler getireceğini belirtti.</p>', 1, 1, 'published', 1, 1, 1, 1, 340, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('Büyükşehirlerde toplu taşıma ücretlerine zam', 'buyuksehirlerde-toplu-tasima-ucretlerine-zam', 'Belediye meclisi kararıyla toplu taşıma ücret tarifesi güncellendi.', '<p>Büyükşehir belediye meclisinin aldığı kararla birlikte toplu taşıma ücret tarifesinde güncelleme yapıldı. Yeni tarife önümüzdeki ay itibarıyla yürürlüğe girecek.</p><p>Belediye yetkilileri, artışın artan maliyetler nedeniyle kaçınılmaz olduğunu açıkladı.</p>', 1, 1, 'published', 0, 0, 0, 0, 210, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Avrupa Birliği zirvesinde kritik gündem maddeleri', 'avrupa-birligi-zirvesinde-kritik-gundem-maddeleri', 'Liderler, ekonomiden göçe kadar geniş bir yelpazede bir araya geldi.', '<p>Brüksel''de düzenlenen zirvede AB liderleri, birliğin gündemindeki kritik konuları masaya yatırdı. Zirve sonrasında ortak bir bildiri yayımlanması bekleniyor.</p><p>Görüşmelerde ekonomik iş birliği ve göç politikaları öne çıkan başlıklar arasında yer aldı.</p>', 2, 1, 'published', 0, 1, 4, 1, 150, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('Uzak Doğu''da doğal afet sonrası yardım seferberliği', 'uzak-doguda-dogal-afet-sonrasi-yardim-seferberligi', 'Bölgede yaşanan afetin ardından uluslararası yardım kuruluşları harekete geçti.', '<p>Bölgeyi etkileyen doğal afetin ardından çok sayıda ülke ve yardım kuruluşu destek göndermeye başladı. Bölgede arama kurtarma çalışmaları sürüyor.</p><p>Yetkililer, afetten etkilenen bölgelere ulaşımın kısmen sağlandığını duyurdu.</p>', 2, 1, 'published', 0, 0, 0, 0, 90, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Merkez Bankası faiz kararını açıkladı', 'merkez-bankasi-faiz-kararini-acikladi', 'Piyasaların yakından takip ettiği toplantıda faiz oranlarına ilişkin karar duyuruldu.', '<p>Para Politikası Kurulu''nun bugünkü toplantısında alınan karar, ekonomi çevrelerinde geniş yankı buldu. Analistler, kararın piyasalara olumlu yansıyacağını değerlendiriyor.</p><p>Karar sonrası yapılan açıklamada, enflasyonla mücadelenin kararlılıkla süreceği vurgulandı.</p>', 3, 1, 'published', 1, 1, 2, 1, 520, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('Enflasyon rakamları beklentileri aştı', 'enflasyon-rakamlari-beklentileri-asti', 'Açıklanan veriler, piyasa beklentilerinin üzerinde gerçekleşti.', '<p>Açıklanan enflasyon verileri, ekonomistlerin beklentilerinin üzerinde geldi. Gıda ve enerji fiyatlarındaki artış, verinin yükselmesinde etkili oldu.</p><p>Uzmanlar, önümüzdeki aylarda alınacak tedbirlerin yakından izleneceğini belirtiyor.</p>', 3, 1, 'published', 0, 0, 0, 0, 300, DATE_SUB(NOW(), INTERVAL 8 HOUR)),
('Milli takım play-off eşleşmesini öğrendi', 'milli-takim-play-off-eslesmesini-ogrendi', 'Kura sonucunda rakip belli oldu, ilk maç yakında oynanacak.', '<p>Yapılan kura çekiminin ardından milli takımın play-off turundaki rakibi netleşti. Teknik heyet, maçlara yönelik hazırlıklara hemen başladı.</p><p>Taraftarlar, biletlerin satışa çıkmasını bekliyor.</p>', 4, 1, 'published', 1, 1, 3, 1, 610, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('Transfer sezonunda flaş gelişme', 'transfer-sezonunda-flas-gelisme', 'Süper Lig ekiplerinden biri, dikkat çeken bir transferi resmileştirdi.', '<p>Transfer döneminin en çok konuşulan gelişmelerinden biri gerçekleşti. Kulüp, yaptığı resmi açıklamayla transferi duyurdu.</p><p>Oyuncunun takıma katkı sağlaması bekleniyor.</p>', 4, 1, 'published', 0, 0, 0, 0, 280, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Yapay zeka destekli yeni işletim sistemi tanıtıldı', 'yapay-zeka-destekli-yeni-isletim-sistemi-tanitildi', 'Teknoloji devi, kullanıcı deneyimini yapay zekayla güçlendiren güncellemeyi duyurdu.', '<p>Şirket düzenlediği tanıtım etkinliğinde, yapay zeka destekli özelliklerle donatılmış yeni işletim sistemini kullanıcılarla buluşturdu. Güncellemenin kademeli olarak dağıtılacağı belirtildi.</p><p>Yeni sürümde performans ve güvenlik alanında da iyileştirmeler yapıldığı açıklandı.</p>', 5, 1, 'published', 0, 1, 5, 0, 195, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('Elektrikli araç satışlarında rekor kırıldı', 'elektrikli-arac-satislarinda-rekor-kirildi', 'Geçtiğimiz ay elektrikli araç satış rakamları tüm zamanların en yüksek seviyesine ulaştı.', '<p>Sektör verilerine göre elektrikli araç satışları bu ay rekor seviyeye çıktı. Uzmanlar, artan model çeşitliliği ve teşviklerin bu artışta etkili olduğunu belirtiyor.</p><p>Üreticiler, talebi karşılamak için üretim kapasitelerini artırmayı planlıyor.</p>', 5, 1, 'published', 0, 0, 0, 0, 140, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Ünlü çiftten sürpriz nişan haberi', 'unlu-ciftten-surpriz-nisan-haberi', 'Sosyal medyadan yapılan paylaşım, kısa sürede gündem oldu.', '<p>Ünlü çift, sosyal medya hesaplarından yaptıkları paylaşımla nişanlandıklarını duyurdu. Paylaşım kısa sürede binlerce beğeni aldı.</p><p>Çiftin yakın çevresi, düğün hazırlıklarının da başladığını ifade etti.</p>', 6, 1, 'published', 0, 1, 6, 0, 430, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('Sinema dünyasının yeni yıldızı belli oldu', 'sinema-dunyasinin-yeni-yildizi-belli-oldu', 'Yılın en çok konuşulan yapımında başrol oyuncusu açıklandı.', '<p>Merakla beklenen yapımın başrol oyuncusu nihayet açıklandı. Yapımcılar, çekimlerin önümüzdeki aylarda başlayacağını duyurdu.</p><p>Proje, sinemaseverler tarafından şimdiden büyük ilgiyle karşılandı.</p>', 6, 1, 'published', 0, 0, 0, 0, 175, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Uzmanlardan kış aylarında bağışıklık uyarısı', 'uzmanlardan-kis-aylarinda-bagisiklik-uyarisi', 'Sağlık uzmanları, mevsim geçişinde alınması gereken önlemleri sıraladı.', '<p>Sağlık uzmanları, kış aylarına girerken bağışıklık sistemini güçlü tutmanın önemine dikkat çekti. Dengeli beslenme ve düzenli uyku öneriler arasında yer aldı.</p><p>Uzmanlar, grip aşısının risk gruplarındaki bireyler için önemli bir koruma sağladığını hatırlattı.</p>', 7, 1, 'published', 1, 0, 0, 0, 260, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('Düzenli yürüyüşün faydaları bilim insanlarınca doğrulandı', 'duzenli-yuruyusun-faydalari-bilim-insanlarinca-dogrulandi', 'Yeni bir araştırma, günlük yürüyüşün sağlığa katkılarını ortaya koydu.', '<p>Yapılan yeni bir bilimsel araştırma, düzenli yürüyüşün kalp sağlığından ruh sağlığına kadar birçok alanda olumlu etkileri olduğunu ortaya koydu.</p><p>Araştırmacılar, günde otuz dakikalık tempolu yürüyüşün yeterli olduğunu belirtti.</p>', 7, 1, 'published', 0, 0, 0, 0, 120, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Ev dekorasyonunda 2026 trendleri', 'ev-dekorasyonunda-2026-trendleri', 'Bu yıl öne çıkan renk ve malzeme tercihleri belli oldu.', '<p>İç mimarlar, 2026 yılında ev dekorasyonunda doğal malzemelerin ve toprak tonlarının ön planda olacağını belirtiyor. Sürdürülebilir tasarım anlayışı da trendler arasında yer alıyor.</p><p>Uzmanlar, küçük dokunuşlarla evlerin kolayca yenilenebileceğini söylüyor.</p>', 8, 1, 'published', 0, 0, 0, 0, 95, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Uzmanından tasarruflu market alışverişi önerileri', 'uzmanindan-tasarruflu-market-alisverisi-onerileri', 'Bütçe uzmanları, haftalık alışverişte tasarruf sağlayacak yöntemleri paylaştı.', '<p>Bütçe uzmanları, market alışverişinde liste yapmanın ve mevsiminde ürün tercih etmenin tasarruf sağladığını belirtiyor. Plansız alışverişin bütçeyi olumsuz etkilediği vurgulandı.</p><p>Uzmanlar, indirim dönemlerinin de takip edilmesini öneriyor.</p>', 8, 1, 'published', 0, 0, 0, 0, 88, DATE_SUB(NOW(), INTERVAL 4 DAY));

CREATE TABLE news_tags (
    news_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (news_id, tag_id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE news_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Yorumlar
-- --------------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Piyasa / doviz bandi (ust ticker icin)
-- --------------------------------------------------------
CREATE TABLE market_ticker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    label VARCHAR(50) NOT NULL,
    value VARCHAR(30) NOT NULL,
    change_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO market_ticker (code, label, value, change_percent, sort_order) VALUES
('USD', 'DOLAR', '48,60', 0.16, 1),
('EUR', 'EURO', '56,48', -0.03, 2),
('GBP', 'STERLIN', '65,80', 0.20, 3),
('XAU', 'ALTIN', '6.854', 1.75, 4),
('XAG', 'GUMUS', '101,42', 2.19, 5),
('BIST', 'BIST 100', '14.419', 0.18, 6),
('BTC', 'BITCOIN', '3.834.363', 2.31, 7);

-- --------------------------------------------------------
-- Site ayarlari (key-value)
-- --------------------------------------------------------
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'bilsenneoldu.com.tr'),
('site_slogan', 'Türkiye''nin Gündemi Burada'),
('logo_path', 'logo/default-logo.svg'),
('phone', ''),
('whatsapp_ihbar', ''),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('youtube_url', ''),
('footer_text', '© 2026 bilsenneoldu.com.tr. Tum haklari saklidir.'),
('meta_description', 'Son dakika haberleri, guncel gelismeler ve daha fazlasi.'),
-- Hava durumu widget'i (includes/weather.php)
('weather_city', 'İstanbul'),
('weather_lat', '41.0082'),
('weather_lon', '28.9784'),
('weather_cache_json', ''),
('weather_cache_time', ''),
-- Canli piyasa verisi (includes/market_api.php)
('market_cron_token', ''),
('market_last_auto_update', ''),
('market_auto_update_enabled', '0');

-- --------------------------------------------------------
-- Medya kutuphanesi
-- --------------------------------------------------------
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Basarisiz giris denemeleri (brute-force korumasi icin)
-- --------------------------------------------------------
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, created_at)
) ENGINE=InnoDB;
