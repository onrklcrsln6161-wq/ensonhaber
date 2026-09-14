# Yayına hazırlık — güncel durum

Tamamlanan geliştirmeler:
- Yönetici, editör ve yazar yetkileri ve ayrı panel adresleri.
- Sosyal medya yönetimi, kurumsal sayfalar, NewsArticle yapılandırılmış verisi.
- Yazar biyografisi ve haber listesi: profil ekranından düzenleme, haberlerden erişim.
- Haber revizyonları: başlık, özet, içerik ve kapak görseli geri alınabilir. Etiket ve galeri revizyonu bu kapsamda değildir.
- Çöp kutusu: haberler yayından kaldırılıp saklanır; geri alınanlar taslak kalır.
- Haber ve kurumsal ayar işlem günlüğü; son 200 kayıt gösterilir.
- Yayın hazırlık ekranı: admin/readiness.php.
- CLI migrasyon ve veritabanı yedekleme. SQLite geri yüklemesi izole ortamda denendi.

Kullanıcı/üretim bilgisi gerektirenler:
- Gerçek sosyal hesap adresleri, künye, iletişim, gizlilik ve yayın politikası metinleri.
- Gerçek yazar biyografileri ve haber/görsel yayın hakları.
- Sunucu erişimi, üretim MySQL ve HTTPS kurulumu; MySQL yedek/geri dönüş doğrulaması.
- SMTP hesabı ve e-posta teslimatı: henüz gönderim entegrasyonu yok.
- Gerçek zamanlı borsa/altın sağlayıcısı ve lisansı: mevcut karma manuel/API değerleri anlık fiyat değildir.
- Sunucuda zamanlanmış görev ve otomatik yedek çalıştırma; uploads yedeği ve izleme.
- Gerçek alan adında Search Console/SEO kontrolleri.

Bu liste tüm olası ürün özelliklerinin veya mevzuata uygunluğun tamamlandığı anlamına gelmez. Yayın hazırlık ekranı alan doluluğunu gösterir; dış servisleri doğrulamaz.

Kaynaklar:
https://support.google.com/news/publisher-center/answer/6204050?hl=en-GB
https://developers.google.com/search/docs/appearance/structured-data/article
