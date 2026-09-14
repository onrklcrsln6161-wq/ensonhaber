# Bilsenneoldu — PHP Haber Portalı

PHP tabanlı haber sitesi; yönetici/editör/yazar panelleri, haber onay akışı, revizyonlar, RSS/Atom aktarımı ve isteğe bağlı AI editör yardımı.

## GitHub'dan kurulum

1. PHP 8.2+ ve MySQL hazırlayın; PDO MySQL, mbstring, DOM/SimpleXML, fileinfo ve OpenSSL uzantılarını etkinleştirin.
2. config/config.example.php dosyasını config/config.php olarak kopyalayın. Veritabanı ve BASE_URL değerlerini ortam değişkenleriyle veya yerel yapılandırmayla ayarlayın.
3. config/database.sql dosyasını MySQL'e aktarın. Bu dosya yalnızca kurulum şeması ve örnek içeriktir; çalışan sitenin verileri değildir.
4. Proje kökünde php scripts/migrate.php çalıştırın.
5. uploads ve config dizinlerinin gerekli yazma izinlerini düzenleyin. config, includes, scripts ve tests dizinlerine web erişimini engelleyin (Apache kuralları dosyalarda bulunur).
6. İlk girişte örnek yönetici parolasını değiştirin. Üretimde HTTPS kullanın.

Çalışan sitenin SQLite veritabanı, yedekleri, günlükleri ve yerel yapılandırması bu depoya dahil değildir. GitHub kod deposudur; PHP uygulaması için ayrıca PHP/MySQL barındırma gerekir.

Yapay zekâ için sunucuda OPENAI_API_KEY tanımlayın ve yönetici panelinden model seçin. Haber botu yalnızca elle çalıştırılır; içe alınan haberler onay bekler.

## Testler

php tests/permissions.php
php tests/security.php
php tests/publishing.php
php tests/editorial.php
php tests/feeds.php
php tests/ai.php

Veritabanı testleri için PDO SQLite gerekir. Üretim erişimi ve API anahtarı gerektiren kontroller yerel testlerden ayrı değerlendirilmelidir.

---

# Haber Portalı

Tam PHP + MySQL ile geliştirilmiş, ensonhaber.com tarzı bir haber sitesi: döviz/altın bandı, kayan "son dakika" bandı, ana sayfa görsel slider'ı, kategori sayfaları, haber detay + yorum sistemi ve profesyonel bir admin panel.

> **Not:** Header ve slider **yapısı/düzeni/UX'i** ensonhaber.com ile birebir aynı kurgulanmıştır (döviz bandı, üst menü, kayan manşet, görsel slider). Ancak ensonhaber.com'un kendi logosu, marka adı ve telif korumalı görselleri kopyalanmamıştır — bunun yerine `admin > Site Ayarları` üzerinden kendi logonuzu/site adınızı tanımlayabileceğiniz beyaz etiket (white-label) bir sistem kuruldu.

## Gereksinimler

- PHP 8.1+ (PDO, pdo_mysql, mbstring, fileinfo, gd uzantıları açık olmalı)
- MySQL 5.7+ / MariaDB 10.3+
- Apache (mod_rewrite gerekmiyor) veya Nginx + PHP-FPM
- Kolay kurulum için: [XAMPP](https://www.apachefriends.org/) / [Laragon](https://laragon.org/) (Windows için önerilir)

## Kurulum

1. **Veritabanını oluşturun**

   ```bash
   mysql -u root -p < config/database.sql
   ```

   Bu komut `haber_portali` veritabanını, tüm tabloları ve varsayılan admin kullanıcısını oluşturur.

2. **Bağlantı bilgilerini girin**

   `config/config.php` dosyasını açıp kendi MySQL bilgilerinizi girin:

   ```php
   define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
   define('DB_NAME', getenv('DB_NAME') ?: 'haber_portali');
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') ?: '');
   ```

   Aynı dosyada `BASE_URL` değerini de projeyi yayınladığınız adrese göre güncelleyin (örn. `http://localhost/haber-portali`).

3. **Klasörü web sunucunuzun kök dizinine kopyalayın** (XAMPP için `htdocs/haber-portali`).

4. **Yükleme klasörlerinin yazma izni olduğundan emin olun**: `uploads/news`, `uploads/logo`, `uploads/media`, `uploads/avatars`.

5. Tarayıcıdan siteyi açın: `http://localhost/haber-portali/index.php`

## Admin Paneline Giriş

`http://localhost/haber-portali/admin/login.php`

- **Kullanıcı adı:** `admin`
- **Şifre:** `Admin123!`

**Önemli:** İlk girişten hemen sonra `Profilim` sayfasından şifrenizi değiştirin.

## Admin Panel Özellikleri

- **Kontrol Paneli** — genel istatistikler (toplam haber, görüntülenme, bekleyen yorum vb.)
- **Haberler** — zengin metin editörlü (Quill) haber ekleme/düzenleme, kapak görseli, galeri, etiketler, taslak/onay/yayın durumu
- **Kategoriler** — sınırsız kategori yönetimi
- **Slider Yönetimi** — ana sayfa slider'ına haber ekleme/çıkarma ve sıralama
- **Yorumlar** — onay bekleyen/onaylı/reddedilen yorum moderasyonu
- **Medya Kütüphanesi** — merkezi görsel yükleme/silme
- **Piyasa Verileri** — üst banttaki döviz/altın/borsa değerlerini elle güncelleme
- **Kullanıcılar** *(süper yönetici)* — editör/yazar/süper yönetici rolleriyle çoklu kullanıcı yönetimi
- **Site Ayarları** *(süper yönetici)* — site adı, logo, sosyal medya, iletişim bilgileri, SEO açıklaması

### Roller

| Rol | Yetki |
|---|---|
| `super_admin` | Her şeye erişim (kullanıcılar, ayarlar dahil) |
| `editor` | Haber, kategori, slider, yorum, medya, piyasa verisi yönetimi |
| `author` | Sadece kendi haberlerini ekleyip düzenleyebilir |

## Güvenlik Notları

- Şifreler `password_hash()` ile bcrypt olarak saklanır.
- Tüm formlarda CSRF token doğrulaması vardır.
- Tüm veritabanı sorguları hazırlanmış ifadeler (prepared statements) ile çalışır.
- `uploads/`, `config/`, `includes/` klasörlerinde `.htaccess` ile doğrudan erişim/script çalıştırma engellenmiştir (Apache için).
- Yüklenen görseller gerçek MIME tipi kontrolünden geçer ve rastgele dosya adıyla kaydedilir.

## Klasör Yapısı

```
haber-portali/
├── admin/              # Yönetim paneli
├── assets/              # Genel site CSS/JS/görseller
├── config/               # Veritabanı bağlantısı ve genel ayarlar
├── includes/            # Ortak PHP bileşenleri (header, footer, slider, fonksiyonlar)
├── uploads/             # Kullanıcı tarafından yüklenen görseller
├── index.php             # Ana sayfa
├── category.php           # Kategori listeleme
├── news.php               # Haber detay + yorumlar
└── search.php             # Arama sonuçları
```

## 13 Eylül 2026 bakım güncellemesi
- Yorum düğmesinin metni ezmesi düzeltildi; uzunluk kontrolü ve oturum başına 60 saniye bekleme eklendi.
- Haber HTML'i kayıtta ve gösterimde izin verilen etiket/özelliklerle yeniden oluşturulur. Gömülü iframe, script, SVG ve stil kaldırılır.
- Başlık düzenlenince haber slug'ı korunur. Form hatalarında girilen alanlar korunur. Quill yüklenmezse HTML metin alanı kullanılabilir.
- Giriş denemeleri PHP saatiyle kaydedilir; SQLite/MySQL saat farkından bağımsız kilitleme uygulanır.
- Haber/piyasa sekmeleri ok tuşları, Home ve End ile kullanılabilir.
- Piyasa kutusu anlık veri iddiasında bulunmaz. BIST/altın/gümüş mevcut sistemde manuel; gerçek zamanlı servis entegrasyonu tamamlanmış değildir.
- Yerel demo: PHP sunucusunu proje kökünde `php -S 127.0.0.1:8090 router.php` ile başlatın; DB_HOST=sqlite, DB_NAME=config/demo.sqlite dosyasının mutlak yolu, BASE_URL=http://127.0.0.1:8090 ortam değişkenlerini tanımlayın. pdo_sqlite, mbstring, fileinfo ve openssl uzantıları gerekir.
- config/demo.sqlite yalnızca yerel demo içindir; canlı sunucuya taşımayın. Üretim MySQL üzerinde ayrıca doğrulanmalıdır.
- Güvenlik regresyonları: `php tests/security.php` (pdo_sqlite ve mbstring etkin).

## Ayrı yönetim panelleri ve roller
- `/admin/`: Yönetici; kullanıcılar, site ayarları, piyasa/veri ayarları ve kalıcı haber silme dahil tüm yönetim.
- `/editor/`: Editör; haber inceleme/yayımlama, kategoriler, slider, yorumlar ve ortak medya kütüphanesi.
- `/author/`: Yazar; kendi taslaklarını ve onay bekleyen haberlerini düzenler, kapak/galeri ekler, editöre gönderir. Yayımlanmış haberi değiştiremez; haber silemez veya doğrudan yayımlayamaz.
Her panelin `login.php` adresi aynı kimlik doğrulamayı kullanır; giriş yapan kullanıcı kendi rolünün paneline yönlendirilir. Kullanıcı rolleri Yönetici > Kullanıcılar bölümünden atanır. Mevcut SQL rol ve durum alanları kullanılır; veri göçü gerekmez.
Yetkiler yalnızca menüde değil, sunucuda da denetlenir. Eski admin GET bağlantıları izin verilen sayfalarda rol paneline yönlenir; yanlış panel üzerinden POST reddedilir.
Kontrol: `php tests/permissions.php`.

## Editoryal geliştirmeler — 13 Eylül 2026
Tamamlandı: yazar biyografisi ve herkese açık yazar haberleri; haberden yazara bağlantı; eski başlık/özet/içerik/kapak sürümlerini saklama ve taslak olarak geri alma; geri alınabilir çöp kutusu; haber ve kurumsal ayar işlemlerinin günlüğü; yönetici yayın hazırlık ekranı; CLI SQL yedekleme.

Kurulum/güncelleme sırası: veritabanını yedekleyin, dosyaları yükleyin, sunucunun DB ortam değişkenleriyle `php scripts/migrate.php` çalıştırın. Sonra siteyi açın. Migrasyon tekrar çalıştırılabilir; var olan haberleri değiştirmez. Demo SQLite migrasyonu uygulandı. MySQL migrasyon ve yedekleme kodları üretim MySQL üzerinde henüz denenmedi.

Yedek: `php scripts/backup.php`. SQL dosyası `config/backups` içine kaydedilir. Yalnızca aynı veritabanı motorunda boş bir veritabanına geri yükleyin. MySQL dışa aktarımı tabloları/verileri kapsar; özel prosedür, event ve trigger yedeği için sunucunun yedekleme aracını kullanın. Görselleri/uploads klasörünü ayrıca yedekleyin. Canlı veritabanına doğrudan geri yükleme yapmayın.

Doğrulama: 44 izin, 10 editoryal, 10 yayın ayarı ve 7 mevcut güvenlik kontrolü geçti. 11 yönetici/yazar sayfası girişli HTTP kontrolünden geçti. Demo SQLite yedeği izole bellekte geri yüklendi ve tablo kayıt sayıları eşleşti.

## Dünya haber botu
`php scripts/migrate-feeds.php` migrasyonunu bir kez çalıştırın (tekrar çalıştırılabilir). Yönetici/Editör > Dünya Haber Botu ekranında bir kaynak seçip haberleri çekin. Sabit HTTPS kaynak listesi BBC dünya/bölge akışlarını ve Guardian Dünya'yı kapsar; internetin bütün haberlerini taradığı iddia edilmez.

Bot en fazla 30 başlığı/kaynağı bir çalıştırmada alır; URL üzerinden tekilleştirir; kaydı yalnızca `pending` yapar. Kaynak başlığı, bağlantısı ve tarihi saklanır. Tam metin veya lisanslı görsel indirmez, otomatik çeviri/yayın yapmaz. Editör kaynak bağlantısını kontrol ederek Türkçe içeriği hazırlar ve yayımlar.

Sunucu zamanlayıcısında (örneğin 30 dakikada bir) `php scripts/import-feeds.php` komutunu DB/BASE_URL ortam değişkenleri ve openssl/mbstring/PDO uzantılarıyla çalıştırın. Zamanlanmış tarama yalnızca panelde seçilen kaynakları kullanır. Bu sürüm işletim sistemine zamanlayıcı kurmaz. Ağ/SSL hatalarında doğrulamayı kapatmak yerine sunucunun sertifika deposunu düzeltin.

Bu oturumda kaynak RSS akışından alınan 10 haber kısa Türkçe özetleri ve kaynak bağlantılarıyla özel olarak yayımlandı. Sonraki bot çalışmaları yayımlama yapmaz. Önceden yayımlanan demo haberler kaldırılmadı.

## Manuel kaynaklar ve yapay zekâ
Güncel davranış: Haber botu manuel kullanılır. Yönetici > Haber Botu > Kaynak ekle/kaldır ekranı, genel erişime açık HTTPS RSS 2.0/Atom kaynaklarını doğrulayıp kaydeder. RSS sunmayan sitelere özel entegrasyon gerekir. İç ağ adresleri, yönlendirmeler, DTD ve büyük yanıtlar engellenir. Alan adı çözümlemesinden sonra doğrulanan IP'ye TLS host doğrulamasıyla bağlanılır. Kaynak eklemek haber çekmez. Elle CLI kullanımı `php scripts/import-feeds.php --manual`; parametresiz çağrı işlem yapmaz. Önceki zamanlayıcı önerisi bu manuel davranışla değiştirilmiştir.

Haber formunda AI: başlık, spot veya içerik dilini düzenleme; öneriyi ayrı alanda inceleme ve son uygulamayı geri alma. API'ye yalnızca düğmeye basıldığında editör metni gönderilir. Haber otomatik kaydedilmez/yayımlanmaz.

Sunucuda OPENAI_API_KEY ortam değişkenini tanımlayın; PHP işlemini yeniden başlatın. Yönetici > Yapay Zekâ Ayarları ekranında hesabınızın erişebildiği Responses API model kimliğini girip etkinleştirin. API anahtarını GitHub'a veya haber metnine koymayın. Öneriler hatalı olabilir; editör kontrolü gereklidir. Requests use store=false; bu, sağlayıcının tüm veri saklama politikalarının kapandığı anlamına gelmez. API kullanımının ayrıca maliyeti olabilir.

Resmi API: https://developers.openai.com/api/reference/cli/resources/responses/methods/create
Canlı AI çağrısı API anahtarı olmadığından denenmedi. HTTP yapılandırma hatası, yanıt çözümleme, RSS/Atom, tekilleştirme ve yayınlamama kontrolleri test edildi.

## 14 Eylül 2026 kontrolü
- Haber kaydında geçersiz/pasif kategori ve uzun etiket doğrulaması eklendi; hata halinde form korunur.
- Eşzamanlı haber düzenlemelerinde sürüm belirteci kontrol edilir; eski form yeni kaydın üzerine yazamaz. Durum ve yetki kayıt anında tekrar denetlenir.
- Slider ana sayfa/panel limiti 8 olarak eşitlendi; taslak/çöp kutusu haberlerinin slider'a eklenmesi engellendi.
- Kullanıcı kaldırma pasifleştirir; yazarlık ilişkileri korunur. Yönetici kendi hesabını pasifleştiremez/rolünü düşüremez.
- Demo router gizli dosyaları engeller; Apache hassas dosya kuralları genişletildi.
- `php scripts/migrate.php` artık RSS tablolarını da kurar; ayrıca migrate-feeds çalıştırmak gerekmez.
- Testler: 86 birim/izin kontrolü, izole veritabanında 24 HTTP iş akışı kontrolü ve SQLite yedekten geri dönüş doğrulaması geçti. PHP dosyaları sözdizimi kontrolünden geçti.
- HTTP testini yeniden çalıştırmak için `tests/prepare-http.php` ile QA kopyasını oluşturun; DB_NAME=config/qa-check.sqlite ve BASE_URL=http://127.0.0.1:8091 ile router sunucusunu 8091'de çalıştırın; `node tests/http-workflows.cjs` kullanın. QA kimlikleri yalnızca test kopyasında bulunur. Canlı veritabanında bu testleri çalıştırmayın.
- API anahtarı yokken gerçek AI üretimi, üretim MySQL/HTTPS ve SMTP teslimatı doğrulanmış sayılmaz.
