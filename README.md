# Zeni Paperworks

**Zeni Paperworks** is a modern invoicing and quotation management system inspired by B**k***, but much more affordable and customizable. Built with Laravel and Vue.js.

## 🚀 Features

### Core Features
- **Invoice Management** - Create, manage, and track invoices with ease
- **Quotation System** - Generate professional quotations that can be converted to invoices
- **Client Management** - Organize and manage your client database
- **Product Catalog** - Maintain a product/service catalog with pricing
- **Tax Management** - Flexible tax system with add/subtract capabilities
- **Multi-Bank Support** - Manage multiple bank accounts for payments
- **Professional Templates** - Beautiful, print-ready invoice and quotation templates

### Advanced Features
- **Smart Filtering** - Filter invoices and quotations by status, date, and client
- **Auto-numbering** - Automatic invoice and quotation number generation
- **Tax Calculator** - Dynamic tax calculation with multiple tax support
- **Quote to Invoice** - Convert quotations to invoices with one click
- **Company Settings** - Customize company information and document prefixes
- **Role-based Access** - Super Admin, Admin, and User roles

## 🛠️ Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Styling**: Tailwind CSS
- **Icons**: Tabler Icons
- **Database**: MySQL/PostgreSQL
- **Build Tool**: Vite

## 📦 Installation

1. Clone the repository
```bash
git clone https://github.com/diorestu/zeni-paperworks.git
cd zeni-paperworks
```

2. Install PHP dependencies
```bash
composer install
```

3. Install Node dependencies
```bash
npm install
```

4. Copy environment file
```bash
cp .env.example .env
```

5. Generate application key
```bash
php artisan key:generate
```

6. Configure your database in `.env` file

7. Run migrations
```bash
php artisan migrate
```

8. Build frontend assets
```bash
npm run build
# or for development
npm run dev
```

9. Start the development server
```bash
php artisan serve
```

## 🎯 Usage

### Default Credentials
After running migrations, you can create a user or use seeded credentials (if available).

### Creating Invoices
1. Navigate to **Invoices** → **Create Invoice**
2. Select a client
3. Add line items (products/services)
4. Apply taxes if needed (click on Tax in Order Summary)
5. Save the invoice

### Managing Taxes
1. Go to **Settings** → **Taxes** tab
2. Add new tax with:
   - Name (e.g., VAT, PPN)
   - Type (Add/Subtract)
   - Rate (percentage)
3. Taxes will be available when creating invoices/quotations

### Converting Quotations to Invoices
1. Create a quotation
2. Open the quotation detail
3. Click **Convert to Invoice**
4. Quotation will be marked as accepted and linked to the new invoice

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

Built with [Laravel](https://laravel.com) - The PHP Framework for Web Artisans.

---

Made with ❤️ by [Dio](https://github.com/diorestu)
