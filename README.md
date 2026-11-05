# TMDT Shobe — E-shopee

Mã nguồn sàn thương mại điện tử gồm Laravel API, MySQL và frontend Vue 3.

Repository của dự án nằm tại [vanhxiiiiiiiiiiii/tmdt_shobe](https://github.com/vanhxiiiiiiiiiiii/tmdt_shobe).

## Cấu trúc

- Thư mục gốc chứa backend Laravel.
- `frontend/` chứa frontend Vue 3 và Vite.
- `database/` chứa migrations, seeders và truy vấn SQL mẫu.
- `docs/` chứa tài liệu thiết kế, API và yêu cầu MySQL.

## Clone source

```powershell
git clone https://github.com/vanhxiiiiiiiiiiii/tmdt_shobe.git
cd tmdt_shobe
```

Frontend đã được lưu trực tiếp trong repo. Có thể chạy hai phần bằng hai cửa sổ PowerShell.

## Chạy backend trên Windows

Cần PHP 8.2 trở lên, Composer và MySQL. Bật extension `pdo_mysql` trong PHP CLI.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Chỉnh kết nối MySQL trong `.env` theo tài khoản trên máy.

```dotenv
APP_URL=http://127.0.0.1:9000
FE_URL=http://127.0.0.1:3333
CORS_ORIGIN=http://127.0.0.1:3333,http://localhost:3333
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_commerce
DB_USERNAME=e_commerce
DB_PASSWORD=your_local_password
REPORT_CACHE_STORE=file
```

Tạo database `e_commerce` và cấp quyền cho tài khoản MySQL trước khi chạy migrations. Với bản cài mới, chạy `php artisan migrate --seed`. Với database đang dùng, chạy `php artisan migrate`.

```powershell
php artisan optimize:clear
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=9000
```

API chạy tại `http://127.0.0.1:9000/api/v2`. Nếu MySQL báo lỗi 1419 khi tạo trigger, quản trị viên MySQL cần cấu hình `log_bin_trust_function_creators` phù hợp cho máy phát triển.

## Chạy frontend

Cần Node.js 22 và pnpm 9.15.9 để dùng lockfile đi kèm.

```powershell
cd frontend
Copy-Item .env.example .env.local
npx pnpm@9.15.9 install --frozen-lockfile
npx pnpm@9.15.9 dev
```

Mở `http://127.0.0.1:3333/buyer/home`. Biến `VITE_BASE_DOMAIN` trong `frontend/.env.local` phải trỏ tới backend đang chạy.

Build frontend bằng `npx pnpm@9.15.9 build` trong thư mục `frontend`.

## GitHub Actions

Workflow `E-shopee CI` kiểm tra PHP, chạy các bài kiểm thử trên MySQL riêng của runner và build frontend khi push hoặc mở pull request vào `main`, `master`, `2.x.x`.

Workflow hạ tầng Azure được chạy thủ công qua Actions và cần cấu hình Terraform/Azure riêng. Các cấu hình dùng image `ghcr.io/vanhxiiiiiiiiiiii/tmdt_shobe:latest` yêu cầu image đã được build và publish trước. CI hiện thực hiện kiểm tra source.

Khi dùng Azure, chuẩn bị chứng chỉ tại `.infrastructure/azure/modules/k8s/dev/tls/tls.crt` và khóa tại `tls/tls.key`. Khi dùng Kubernetes cục bộ, thay các giá trị mẫu trong `.infrastructure/local/ingress/02-tls-secret.yml` bằng TLS Secret của môi trường triển khai.

Workflow tham khảo [actions/checkout](https://github.com/actions/checkout) và [setup-php](https://github.com/shivammathur/setup-php).

## Đưa source lên GitHub

Trong thư mục gốc của bản source đã chuẩn bị trên máy hiện tại, chạy lần đầu.

```powershell
git add .
git commit -m "Prepare Laravel and Vue project for tmdt_shobe"
git push -u origin HEAD:main
```

Các file `.env`, dependencies, frontend build, cache và bản backup SQL trong `storage/app` được bỏ qua bởi Git. File SQL mẫu nằm ở [database/eshopee-report-queries.sql](database/eshopee-report-queries.sql).

## Tài liệu và nguồn gốc

- [Yêu cầu và thiết kế MySQL](docs/MYSQL_REQUIREMENTS.md)
- [Truy vấn SQL mẫu](database/eshopee-report-queries.sql)
- [Tài liệu API](docs/index.html)
- [Hạ tầng Kubernetes cục bộ](.infrastructure/local/README.md)
- [Hạ tầng Azure](.infrastructure/azure)
- Backend phát triển từ [TP-O/e-commerce-api](https://github.com/TP-O/e-commerce-api).
- Frontend phát triển từ [thuongtruong1009/e-shopee](https://github.com/thuongtruong1009/e-shopee).

