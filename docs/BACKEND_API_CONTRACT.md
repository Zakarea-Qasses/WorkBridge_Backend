# Backend API Contract

## Project Overview

- Backend framework: Laravel 12, PHP `^8.2`.
- API auth package: Laravel Sanctum `^4.3`.
- Main API route file: `routes/api.php`.
- Web route file: `routes/web.php` only serves `/`.
- Controllers:
  - Auth: `app/Http/Controllers/Auth/AuthController.php`, `ForgotPasswordController.php`, `ResetPasswordController.php`.
  - API: `app/Http/Controllers/Api/*.php`.
- Services:
  - `app/Services/ContractService.php`
  - `app/Services/WalletService.php`
- Models: `app/Models/*.php`.
- Validation: inline `$request->validate(...)` inside controllers. No `app/Http/Requests` Form Request classes found.
- Custom middleware:
  - `role`: `app/Http/Middleware/RoleMiddleware.php`
  - `verified`: `app/Http/Middleware/EnsureEmailIsVerified.php`
- Default API prefix: all `routes/api.php` routes are exposed under `/api/...`.

## How To Run Backend

Expected local setup:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Composer scripts also define:

```bash
composer run dev
composer run test
```

Current `.env` uses SQLite:

```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=C:\Users\Noor\Desktop\WorkBridge_Backend\database\database.sqlite
```

## Authentication System

- Login endpoint: `POST /api/login`.
- Logout endpoint: `POST /api/logout`.
- Current user endpoints:
  - `GET /api/user`: requires `auth:sanctum`.
  - `GET /api/me`: requires `auth:sanctum` and verified email.
- Token creation: `AuthController@login` calls `$user->createToken('api-token')->plainTextToken`.
- Frontend should send token as:

```http
Authorization: Bearer <token>
Accept: application/json
```

- Token verification happens through Laravel Sanctum middleware `auth:sanctum`.
- Email verification is enforced by custom `verified` middleware.
- Role authorization is enforced by custom `role:<role>` middleware.
- Login response shape:

```json
{
  "message": "string",
  "token": "plain_text_sanctum_token",
  "user": {
    "id": 1,
    "name": "string",
    "email": "string",
    "role": "personal|company|admin",
    "status": "unactive|pending_review|under_review|active|blocked",
    "email_verified_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
  },
  "dashboard": {
    "role": "personal|company|admin",
    "url": "/api/dashboard/personal|/api/dashboard/company|/api/dashboard/admin"
  }
}
```

Important login rules:

- Email must be verified before login succeeds.
- Blocked users cannot login.
- Company users with `status = unactive` cannot login until admin approval/verification.
- Non-admin users must have `status = active`.

## Required Environment Variables

Required/important variables from current config:

| Variable | Purpose | Current / default |
|---|---|---|
| `APP_NAME` | Application name | `WorkBridge` |
| `APP_ENV` | Runtime environment | `local` |
| `APP_KEY` | Laravel encryption key; required for encrypted messages and app security | currently empty |
| `APP_DEBUG` | Debug responses | `true` |
| `APP_URL` | Backend base URL and generated asset/storage URLs | `http://127.0.0.1:8000` |
| `APP_LOCALE` | App locale | default `ar` |
| `APP_FALLBACK_LOCALE` | Fallback locale | default `ar` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `DB_DATABASE` | SQLite file or DB name/path | local sqlite absolute path |
| `DB_URL` | Optional database URL | supported by config |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | Needed if using MySQL/MariaDB/Postgres/SQL Server | config-supported |
| `MAIL_MAILER` | Mail transport for OTP/password reset | `log` |
| `MAIL_FROM_ADDRESS` | Sender address | `hello@workbridge.test` |
| `MAIL_FROM_NAME` | Sender name | `WorkBridge` |
| `QUEUE_CONNECTION` | Queue backend | `sync` |
| `CACHE_STORE` | Cache store | `database` |
| `SESSION_DRIVER` | Session storage | `database` |
| `SANCTUM_STATEFUL_DOMAINS` | Optional for cookie-based Sanctum SPA auth | not set |
| `SANCTUM_TOKEN_PREFIX` | Optional Sanctum token prefix | not set |

Not found but likely needed for frontend integration if browser CORS issues appear:

- `FRONTEND_URL`
- `CORS_ALLOWED_ORIGINS`

## API Endpoints Table

Total API endpoints found in `routes/api.php`: **114**.

All paths below include the Laravel API prefix `/api`.

| Feature | Method | URL path | Controller/function | Auth |
|---|---:|---|---|---|
| Current user raw | GET | `/api/user` | closure | Sanctum |
| Register | POST | `/api/register` | `AuthController@register` | Public |
| Login | POST | `/api/login` | `AuthController@login` | Public |
| Verify email OTP | POST | `/api/email/verify` | `AuthController@verify` | Public |
| Resend email OTP | POST | `/api/email/resend` | `AuthController@resend` | Public |
| Forgot password | POST | `/api/forgot-password` | `ForgotPasswordController@forgotPassword` | Public |
| Reset password | POST | `/api/reset-password` | `ResetPasswordController@resetPassword` | Public |
| Reset token echo | GET | `/api/reset-password/{token}` | closure | Public |
| Logout | POST | `/api/logout` | `AuthController@logout` | Sanctum |
| Current user wrapped | GET | `/api/me` | closure | Sanctum + verified |
| Personal dashboard | GET | `/api/dashboard/personal` | `DashboardController@personal` | Sanctum + verified + role:personal |
| Company dashboard | GET | `/api/dashboard/company` | `DashboardController@company` | Sanctum + verified + role:company |
| Admin dashboard | GET | `/api/dashboard/admin` | `DashboardController@admin` | Sanctum + verified + role:admin |
| Personal profile show | GET | `/api/profile` | `ProfileController@show` | Sanctum + verified + role:personal |
| Personal profile update | PUT | `/api/profile` | `ProfileController@update` | Sanctum + verified + role:personal |
| Company profile show | GET | `/api/company` | `CompanyController@show` | Sanctum + verified + role:company |
| Company profile update | PUT | `/api/company` | `CompanyController@update` | Sanctum + verified + role:company |
| My wallet | GET | `/api/wallet` | `WalletController@myWallet` | Sanctum |
| Deposit wallet | POST | `/api/wallet/deposit` | `WalletController@deposit` | Sanctum |
| Withdraw wallet | POST | `/api/wallet/withdraw` | `WalletController@withdraw` | Sanctum |
| Transfer to admin wallet | POST | `/api/wallet/transfer-to-admin` | `WalletController@transferToAdmin` | Sanctum |
| My contracts | GET | `/api/contracts` | `ContractController@index` | Sanctum |
| Contract details | GET | `/api/contracts/{id}` | `ContractController@show` | Sanctum |
| Start/fund contract | POST | `/api/contracts/{id}/start` | `ContractController@start` | Sanctum |
| Complete contract | POST | `/api/contracts/{id}/complete` | `ContractController@complete` | Sanctum |
| Cancel contract | POST | `/api/contracts/{id}/cancel` | `ContractController@cancel` | Sanctum |
| Create review | POST | `/api/reviews` | `ReviewController@store` | Sanctum |
| User reviews | GET | `/api/users/{userId}/reviews` | `ReviewController@userReviews` | Public |
| Notifications list | GET | `/api/notifications` | `UserNotificationController@index` | Sanctum + verified |
| Unread notifications count | GET | `/api/notifications/unread-count` | `UserNotificationController@unreadCount` | Sanctum + verified |
| Mark notification read | POST | `/api/notifications/{id}/read` | `UserNotificationController@markAsRead` | Sanctum + verified |
| Mark all notifications read | POST | `/api/notifications/read-all` | `UserNotificationController@markAllAsRead` | Sanctum + verified |
| Delete notification | DELETE | `/api/notifications/{id}` | `UserNotificationController@destroy` | Sanctum + verified |
| Settings show | GET | `/api/settings` | `UserSettingController@show` | Sanctum + verified |
| Update privacy settings | PUT | `/api/settings/privacy` | `UserSettingController@updatePrivacy` | Sanctum + verified |
| Update notification settings | PUT | `/api/settings/notifications` | `UserSettingController@updateNotifications` | Sanctum + verified |
| Update password | PUT | `/api/settings/password` | `UserSettingController@updatePassword` | Sanctum + verified |
| Clear local data | DELETE | `/api/settings/local-data` | `UserSettingController@clearLocalData` | Sanctum + verified |
| Governorates | GET | `/api/governorates` | `LocationController@governorates` | Public |
| Cities | GET | `/api/cities` | `LocationController@allCities` | Public |
| City details | GET | `/api/cities/{id}` | `LocationController@city` | Public |
| Governorate cities | GET | `/api/governorates/{id}/cities` | `LocationController@cities` | Public |
| Categories | GET | `/api/categories` | `CategoryController@index` | Public |
| Services list | GET | `/api/services` | `ServiceController@index` | Public |
| Service details | GET | `/api/services/{id}` | `ServiceController@show` | Public |
| Create service | POST | `/api/services` | `ServiceController@store` | Sanctum |
| Update service | PUT | `/api/services/{id}` | `ServiceController@update` | Sanctum |
| Delete service | DELETE | `/api/services/{id}` | `ServiceController@destroy` | Sanctum |
| Projects list | GET | `/api/projects` | `UserProjectController@index` | Public |
| Project details | GET | `/api/projects/{id}` | `UserProjectController@show` | Public |
| Create project | POST | `/api/projects` | `UserProjectController@store` | Sanctum |
| Update project | PUT | `/api/projects/{id}` | `UserProjectController@update` | Sanctum |
| Delete project | DELETE | `/api/projects/{id}` | `UserProjectController@destroy` | Sanctum |
| Start conversation | POST | `/api/conversations/start` | `ConversationController@start` | Sanctum + verified |
| My conversations | GET | `/api/conversations` | `ConversationController@myConversations` | Sanctum + verified |
| Conversation messages | GET | `/api/conversations/{conversation}/messages` | `ConversationController@messages` | Sanctum + verified |
| Send message | POST | `/api/conversations/{conversation}/messages` | `ConversationController@sendMessage` | Sanctum + verified |
| Mark conversation read | POST | `/api/conversations/{conversation}/read` | `ConversationController@markAsRead` | Sanctum + verified |
| Apply to project | POST | `/api/projects/{projectId}/applications` | `ApplicationController@store` | Sanctum |
| Received applications | GET | `/api/applications/received` | `ApplicationController@received` | Sanctum |
| My applications | GET | `/api/applications/my` | `ApplicationController@myApplications` | Sanctum |
| Accept application | POST | `/api/applications/{id}/accept` | `ApplicationController@accept` | Sanctum |
| Reject application | POST | `/api/applications/{id}/reject` | `ApplicationController@reject` | Sanctum |
| Request service | POST | `/api/services/{service}/requests` | `ServiceRequestController@store` | Sanctum |
| Received service requests | GET | `/api/service-requests/received` | `ServiceRequestController@received` | Sanctum |
| My service requests | GET | `/api/service-requests/my` | `ServiceRequestController@myRequests` | Sanctum |
| Accept service request | POST | `/api/service-requests/{id}/accept` | `ServiceRequestController@accept` | Sanctum |
| Reject service request | POST | `/api/service-requests/{id}/reject` | `ServiceRequestController@reject` | Sanctum |
| Create report | POST | `/api/reports` | `ReportController@store` | Sanctum |
| My reports | GET | `/api/reports/my` | `ReportController@myReports` | Sanctum |
| Latest my report | GET | `/api/reports/latest` | `ReportController@latestMine` | Sanctum |
| Admin reports | GET | `/api/reports` | `ReportController@index` | Sanctum + role:admin |
| Admin report decision | PUT | `/api/reports/{id}/decision` | `ReportController@adminDecision` | Sanctum + role:admin |
| Admin users | GET | `/api/admin/users` | `AdminUserController@index` | Sanctum + role:admin |
| Admin all wallets | GET | `/api/admin/wallets` | `WalletController@allWallets` | Sanctum + role:admin |
| Admin transactions | GET | `/api/admin/transactions` | `WalletController@adminTransactions` | Sanctum + role:admin |
| Admin escrow transactions | GET | `/api/admin/escrow/transactions` | `WalletController@escrowTransactions` | Sanctum + role:admin |
| Admin earnings | GET | `/api/admin/earnings` | `WalletController@adminEarnings` | Sanctum + role:admin |
| Admin settings show | GET | `/api/admin/settings` | `AdminSettingController@show` | Sanctum + role:admin |
| Admin settings update | PUT | `/api/admin/settings` | `AdminSettingController@update` | Sanctum + role:admin |
| Admin user review board | GET | `/api/admin/users/review-board` | `AdminUserController@reviewBoard` | Sanctum + role:admin |
| Admin user details | GET | `/api/admin/users/{id}` | `AdminUserController@show` | Sanctum + role:admin |
| Admin mark user under review | POST | `/api/admin/users/{id}/under-review` | `AdminUserController@markUnderReview` | Sanctum + role:admin |
| Admin approve user | POST | `/api/admin/users/{id}/approve` | `AdminUserController@approve` | Sanctum + role:admin |
| Admin block user | POST | `/api/admin/users/{id}/block` | `AdminUserController@block` | Sanctum + role:admin |
| Admin delete user | DELETE | `/api/admin/users/{id}` | `AdminUserController@destroy` | Sanctum + role:admin |
| Admin companies | GET | `/api/admin/companies` | `AdminCompanyVerificationController@index` | Sanctum + role:admin |
| Admin pending companies | GET | `/api/admin/companies/pending` | `AdminCompanyVerificationController@pending` | Sanctum + role:admin |
| Admin verify company | POST | `/api/admin/companies/{id}/verify` | `AdminCompanyVerificationController@verify` | Sanctum + role:admin |
| Admin unverify company | POST | `/api/admin/companies/{id}/unverify` | `AdminCompanyVerificationController@unverify` | Sanctum + role:admin |
| Admin content projects | GET | `/api/admin/content/projects` | `AdminContentController@projects` | Sanctum + role:admin |
| Admin update project status | PUT | `/api/admin/content/projects/{id}/status` | `AdminContentController@updateProjectStatus` | Sanctum + role:admin |
| Admin delete project | DELETE | `/api/admin/content/projects/{id}` | `AdminContentController@destroyProject` | Sanctum + role:admin |
| Admin content services | GET | `/api/admin/content/services` | `AdminContentController@services` | Sanctum + role:admin |
| Admin update service status | PUT | `/api/admin/content/services/{id}/status` | `AdminContentController@updateServiceStatus` | Sanctum + role:admin |
| Admin delete service | DELETE | `/api/admin/content/services/{id}` | `AdminContentController@destroyService` | Sanctum + role:admin |
| Admin content jobs | GET | `/api/admin/content/jobs` | `AdminContentController@jobs` | Sanctum + role:admin |
| Admin update job status | PUT | `/api/admin/content/jobs/{id}/status` | `AdminContentController@updateJobStatus` | Sanctum + role:admin |
| Admin delete job | DELETE | `/api/admin/content/jobs/{id}` | `AdminContentController@destroyJob` | Sanctum + role:admin |
| Admin categories | GET | `/api/admin/content/categories` | `AdminContentController@categories` | Sanctum + role:admin |
| Admin create category | POST | `/api/admin/content/categories` | `AdminContentController@storeCategory` | Sanctum + role:admin |
| Admin update category | PUT | `/api/admin/content/categories/{id}` | `AdminContentController@updateCategory` | Sanctum + role:admin |
| Admin delete category | DELETE | `/api/admin/content/categories/{id}` | `AdminContentController@destroyCategory` | Sanctum + role:admin |
| Jobs list | GET | `/api/jobs` | `JobPostController@index` | Public |
| Job details | GET | `/api/jobs/{id}` | `JobPostController@show` | Public |
| Company browse services | GET | `/api/company/services` | `ServiceController@companyBrowse` | Sanctum + role:company |
| Company contracts | GET | `/api/company/contracts` | `ContractController@companyContracts` | Sanctum + role:company |
| Create company job contract | POST | `/api/company/jobs/{jobId}/contracts` | `ContractController@createCompanyJobContract` | Sanctum + role:company |
| Company jobs | GET | `/api/company/jobs` | `JobPostController@myJobs` | Sanctum + role:company |
| Create job | POST | `/api/jobs` | `JobPostController@store` | Sanctum + role:company |
| Update job | PUT | `/api/jobs/{id}` | `JobPostController@update` | Sanctum + role:company |
| Pause job | POST | `/api/jobs/{id}/pause` | `JobPostController@pause` | Sanctum + role:company |
| Activate job | POST | `/api/jobs/{id}/activate` | `JobPostController@activate` | Sanctum + role:company |
| Delete job | DELETE | `/api/jobs/{id}` | `JobPostController@destroy` | Sanctum + role:company |

## Detailed API Documentation

### Common Notes

- Route file for all endpoints in this section: `routes/api.php`.
- Authentication type: Bearer Token using Laravel Sanctum unless endpoint is public.
- No endpoint currently uses multipart file upload handling (`hasFile`, `file`, or uploaded file validation were not found).
- Laravel validation errors use the default JSON format when `Accept: application/json` is sent:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["error message"]
  }
}
```

- Common status codes:
  - `200`: success
  - `201`: created
  - `400`: invalid reset token
  - `401`: unauthenticated or invalid login credentials
  - `403`: forbidden, unverified email, blocked/unapproved account, wrong role
  - `404`: model not found
  - `409`: duplicate application
  - `422`: validation/business rule failure
  - `429`: rate limited
  - `500`: mail/logout unexpected error in a few auth paths

### Auth

`POST /api/register`

- Body: `name` required string max 255, `email` required email unique, `password` required min 8 confirmed, `password_confirmation` required, `role` required enum `personal|company`.
- Response `201`: `{ message, user }`.
- Side effects: creates profile for personal users, company record for company users, wallet, OTP email record, admin notification for company users.
- Rate limit: 5 attempts by IP.

`POST /api/login`

- Body: `email` required email, `password` required.
- Response `200`: `{ message, token, user, dashboard: { role, url } }`.
- Rate limit: 5 attempts by IP.

`POST /api/email/verify`

- Body: `email` required email, `otp` required 6 digits.
- Response `200`: `{ message }`.

`POST /api/email/resend`

- Body: `email` required email.
- Response `200`: `{ message }`.
- Rate limit: 3 attempts per user/IP, also enforces at least one minute between OTP sends.

`POST /api/forgot-password`

- Body: `email` required email.
- Response `200`: `{ message }`.
- Rate limit: 3 attempts per email/IP.

`POST /api/reset-password`

- Body: `token` required string, `email` required email, `password` required min 8 confirmed.
- Response `200`: `{ message }`; `400` for invalid/expired reset token.

`GET /api/reset-password/{token}`

- Path params: `token`.
- Response: `{ token }`.

`POST /api/logout`

- Auth: Sanctum.
- Response `200`: `{ message }`.
- Deletes current access token.

`GET /api/user`, `GET /api/me`

- Auth: Sanctum; `/me` also requires verified email.
- Response `/user`: raw authenticated user.
- Response `/me`: `{ user }`.

### Dashboards

`GET /api/dashboard/personal`

- Auth: Sanctum + verified + role `personal`.
- Response: `{ message, role, user, stats, recent_projects, active_contracts, quick_actions }`.

`GET /api/dashboard/company`

- Auth: Sanctum + verified + role `company`.
- Response: `{ message, role, user }`.

`GET /api/dashboard/admin`

- Auth: Sanctum + verified + role `admin`.
- Response: `{ message, role, user, stats, company_verification_requests, content_needing_review, dispute_alerts, charts }`.

### Profiles and Company Profile

`GET /api/profile`

- Auth: Sanctum + verified + role `personal`.
- Response: `{ profile, rating_avg, reviews_count }`.

`PUT /api/profile`

- Auth: Sanctum + verified + role `personal`.
- Body: `name` sometimes string max 100, `job_title` nullable string max 255, `governorate_id` nullable exists, `city_id` nullable exists, `phone` nullable string max 30, `address` nullable string max 255, `description` nullable string, `bio` nullable string, `skills` nullable array of strings.
- Business rule: if both governorate and city are sent, city must belong to governorate.
- Response: `{ message, profile }`.

`GET /api/company`

- Auth: Sanctum + verified + role `company`.
- Response: `{ company }`.

`PUT /api/company`

- Auth: Sanctum + verified + role `company`.
- Body: `company_name` required string max 255, `website` nullable URL, `location` nullable string max 255, `governorate_id` nullable exists, `city_id` nullable exists, `description` nullable string, `phone` nullable string max 30, `skills` nullable array of strings.
- Response: `{ message, company }`.

### Location and Categories

`GET /api/governorates`

- Query: `with_cities` optional boolean.
- Response: array of governorates, optionally with `cities`.

`GET /api/cities`

- Query: `governorate_id` optional.
- Response: array of cities with governorate.

`GET /api/cities/{id}`

- Path params: `id`.
- Response: city with governorate.

`GET /api/governorates/{id}/cities`

- Path params: `id` governorate id.
- Response: array of cities.

`GET /api/categories`

- Response: `{ categories }`.

### Services

`GET /api/services`

- Public.
- Response: `{ services }`, active services only.
- Pagination: no.

`GET /api/services/{id}`

- Public.
- Path params: `id`.
- Response: `{ service }`, active service only.

`GET /api/company/services`

- Auth: Sanctum + role `company`.
- Query: `search` optional string max 255, `category_id` optional exists.
- Response: `{ available_services_count, services }`.
- Pagination: yes, Laravel paginator, 9 per page.
- Search: title, description, owner name.

`POST /api/services`

- Auth: Sanctum. Controller only permits role `personal`.
- Body: `title` required string max 255, `category_id` required exists, `price` required numeric min 0, `delivery_days` required integer min 1, `description` nullable string.
- Response `201`: `{ message, service }`.

`PUT /api/services/{id}`

- Auth: Sanctum. Owner only.
- Body: `title` sometimes string max 255, `category_id` sometimes exists, `price` sometimes numeric min 0, `delivery_days` sometimes integer min 1, `description` nullable string.
- Response: `{ message, service }`.

`DELETE /api/services/{id}`

- Auth: Sanctum. Owner only.
- Response: `{ message }`.

### Projects

`GET /api/projects`

- Public.
- Query filters: `governorate_id`, `city_id`.
- Response: `{ projects }`, active projects only.
- Pagination: no.

`GET /api/projects/{id}`

- Public.
- Response: `{ project }`, active project only.

`POST /api/projects`

- Auth: Sanctum. Controller only permits role `personal`.
- Body: `title` required string max 255, `description` required string, `budget` required numeric min 0, `duration_days` required integer min 1, `category_id` required exists, `governorate_id` nullable exists, `city_id` nullable exists, `skills` required array of skill ids, `skills.*` exists.
- Response `201`: `{ message, project }`.

`PUT /api/projects/{id}`

- Auth: Sanctum. Owner only.
- Body: same fields as create but `sometimes`, except nullable location fields.
- Response: `{ message, project }`.

`DELETE /api/projects/{id}`

- Auth: Sanctum. Owner only.
- Response: `{ message }`.

### Jobs

`GET /api/jobs`

- Public.
- Query filters: `city_id`, `governorate_id`.
- Response: `{ jobs }`, active jobs only.
- Pagination: no.

`GET /api/jobs/{id}`

- Public.
- Response: `{ job }`.
- Note: this endpoint does not filter by `status = active`.

`GET /api/company/jobs`

- Auth: Sanctum + role `company`.
- Response: `{ jobs }`.

`POST /api/jobs`

- Auth: Sanctum + role `company`.
- Body: `title` required string max 255, `description` required string, `location_type` nullable enum `remote|on_site|hybrid`, `city_id` nullable exists, `salary` nullable numeric min 0.
- Response `201`: `{ message, job }`.

`PUT /api/jobs/{id}`

- Auth: Sanctum + role `company`. Owner company only.
- Body: `title` sometimes string max 255, `description` sometimes string, `location_type` nullable enum `remote|on_site|hybrid`, `city_id` nullable exists, `salary` nullable numeric min 0, `status` sometimes enum `active|paused|closed`.
- Response: `{ message, job }`.

`POST /api/jobs/{id}/pause`, `POST /api/jobs/{id}/activate`, `DELETE /api/jobs/{id}`

- Auth: Sanctum + role `company`. Owner company only.
- Response pause/activate: `{ message, job }`.
- Response delete: `{ message }`.

### Applications and Service Requests

`POST /api/projects/{projectId}/applications`

- Auth: Sanctum. Controller only permits role `personal`.
- Body: `price` required numeric min 1, `duration_days` required integer min 1, `description` required string.
- Business rules: cannot apply to own project; duplicate application returns `409`.
- Response `201`: `{ message, application }`.

`GET /api/applications/received`

- Auth: Sanctum.
- Response: `{ applications }` for projects owned by current user.

`GET /api/applications/my`

- Auth: Sanctum.
- Response: `{ applications }`.

`POST /api/applications/{id}/accept`, `POST /api/applications/{id}/reject`

- Auth: Sanctum. Project owner only.
- Accept response: `{ message, application, contract }`.
- Reject response: `{ message, application }`.
- Accept side effect: creates contract and rejects other applications.

`POST /api/services/{service}/requests`

- Auth: Sanctum. Roles `personal|company`.
- Body: `title` required string max 255, `description` required string, `references` nullable string, `delivery_days` required integer min 1.
- Business rule: cannot request own service.
- Response `201`: `{ message, service_request }`.

`GET /api/service-requests/received`, `GET /api/service-requests/my`

- Auth: Sanctum.
- Response: `{ requests }`.

`POST /api/service-requests/{id}/accept`, `POST /api/service-requests/{id}/reject`

- Auth: Sanctum. Service owner only.
- Accept response: `{ message, service_request, contract }`.
- Reject response: `{ message, service_request }`.

### Contracts and Wallets

`GET /api/contracts`

- Auth: Sanctum.
- Response: `{ contracts }` where user is client or freelancer.

`GET /api/contracts/{id}`

- Auth: Sanctum. Client, freelancer, or admin can see.
- Response: `{ contract }`.

`POST /api/contracts/{id}/start`

- Auth: Sanctum. Client only.
- Funds contract from client wallet to escrow.
- Response: `{ message, contract }`.

`POST /api/contracts/{id}/complete`

- Auth: Sanctum. Client only.
- Releases escrow payment to freelancer and admin commission.
- Response: `{ message, contract }`.

`POST /api/contracts/{id}/cancel`

- Auth: Sanctum. Client, freelancer, or admin can cancel.
- Response: `{ message, contract }`.

`GET /api/company/contracts`

- Auth: Sanctum + role `company`.
- Response: `{ contracts }`.
- Pagination: yes, 10 per page.

`POST /api/company/jobs/{jobId}/contracts`

- Auth: Sanctum + role `company`.
- Body: `freelancer_id` required exists users id, `amount` required numeric min 1.
- Response `201`: `{ message, contract }`.

`GET /api/wallet`

- Auth: Sanctum.
- Response: `{ status: true, wallet }` with transactions.

`POST /api/wallet/deposit`, `POST /api/wallet/withdraw`, `POST /api/wallet/transfer-to-admin`

- Auth: Sanctum.
- Body: `amount` required numeric min 1.
- Response: `{ status: true, message, transaction }`.

### Reviews, Reports, Notifications, Conversations, Settings

`POST /api/reviews`

- Auth: Sanctum.
- Body: `contract_id` required exists, `rating` required integer 1..5, `comment` nullable string.
- Business rules: contract must be completed; reviewer must be client or freelancer.
- Response `201`: `{ message, review }`.

`GET /api/users/{userId}/reviews`

- Public.
- Response: `{ rating_avg, reviews_count, reviews }`.

`POST /api/reports`

- Auth: Sanctum.
- Body: `target_type` nullable enum `user|project|service|contract|general`, `target_id` nullable integer, `contract_id` nullable exists, `title` nullable string max 255, `category` nullable enum `support|complaint|dispute|payment|technical`, `priority` nullable enum `low|normal|high`, `description` required string, `attachments` nullable array of strings max 255.
- Upload: no real file upload; attachments are stored as string values.
- Response `201`: `{ message, report }`.

`GET /api/reports/my`

- Auth: Sanctum.
- Response: `{ reports }`.
- Pagination: yes, 10 per page.

`GET /api/reports/latest`

- Auth: Sanctum.
- Response: `{ report }`.

`GET /api/notifications`

- Auth: Sanctum + verified.
- Response: `{ notifications }`.
- Pagination: yes, 20 per page.

`GET /api/notifications/unread-count`

- Auth: Sanctum + verified.
- Response: `{ unread_count }`.

`POST /api/notifications/{id}/read`, `POST /api/notifications/read-all`, `DELETE /api/notifications/{id}`

- Auth: Sanctum + verified.
- Response single read: `{ message, notification }`.
- Response read all: `{ message, updated_count }`.
- Response delete: `{ message }`.

`POST /api/conversations/start`

- Auth: Sanctum + verified.
- Body: `user_id` required integer exists.
- Response: `{ message, conversation }`.

`GET /api/conversations`

- Auth: Sanctum + verified.
- Response: `{ conversations }`.
- Pagination: yes, 20 per page.

`GET /api/conversations/{conversation}/messages`

- Auth: Sanctum + verified. Current user must belong to conversation.
- Response: `{ messages }`.
- Pagination: yes, 20 per page.

`POST /api/conversations/{conversation}/messages`

- Auth: Sanctum + verified.
- Body: `content` required string max 10000, `type` nullable enum `text|image|file`.
- Upload: no file upload despite enum values; content is text and encrypted in model mutator.
- Response `201`: `{ message, data }`.
- Rate limit: 20 messages per minute per user.

`POST /api/conversations/{conversation}/read`

- Auth: Sanctum + verified.
- Response: `{ message, updated_count }`.

`GET /api/settings`

- Auth: Sanctum + verified.
- Response: `{ settings: { privacy, notifications } }`.

`PUT /api/settings/privacy`

- Body: `profile_visible` required boolean, `contact_permission` required enum `all|verified|none`.
- Response: `{ message, settings }`.

`PUT /api/settings/notifications`

- Body: `message_notifications` required boolean.
- Response: `{ message, settings }`.

`PUT /api/settings/password`

- Body: `current_password` required string, `password` required string min 8 confirmed.
- Response: `{ message }`.

`DELETE /api/settings/local-data`

- Response: `{ message, deleted_notifications }`.

### Admin

All admin endpoints require `auth:sanctum` and `role:admin`.

- `GET /api/admin/users`: query `search`, `role=personal|company`, `status=unactive|pending_review|under_review|active|blocked`; response `{ users }`; paginated 10.
- `GET /api/admin/users/review-board`: response `{ unactive, pending_review, under_review, reviewed, counts }`.
- `GET /api/admin/users/{id}`: response `{ user }`.
- `POST /api/admin/users/{id}/under-review`: response `{ message, user }`; deletes user tokens.
- `POST /api/admin/users/{id}/approve`: response `{ message, user }`; company approval also sets `is_verified=true`.
- `POST /api/admin/users/{id}/block`: response `{ message, user }`; deletes user tokens.
- `DELETE /api/admin/users/{id}`: response `{ message }`.
- `GET /api/admin/companies`: query `search`, `is_verified` boolean; response `{ companies }`; paginated 10.
- `GET /api/admin/companies/pending`: response `{ companies }`.
- `POST /api/admin/companies/{id}/verify`: response `{ message, company }`.
- `POST /api/admin/companies/{id}/unverify`: response `{ message, company }`; deletes company user's tokens.
- `GET /api/admin/content/projects`: query `search`, `status=active|paused|closed`, `category_id`, `governorate_id`, `city_id`; response `{ projects }`; paginated 10.
- `GET /api/admin/content/services`: query `search`, `status=active|paused|closed`, `category_id`; response `{ services }`; paginated 10.
- `GET /api/admin/content/jobs`: query `search`, `status=active|paused|closed`, `governorate_id`, `city_id`; response `{ jobs }`; paginated 10.
- `PUT /api/admin/content/projects/{id}/status`, `/services/{id}/status`, `/jobs/{id}/status`: body `status` required enum `active|paused|closed`; response `{ message, project|service|job }`.
- `DELETE /api/admin/content/projects/{id}`, `/services/{id}`, `/jobs/{id}`: response `{ message }`.
- `GET /api/admin/content/categories`: response `{ categories }`.
- `POST /api/admin/content/categories`: body `name` required string max 255 unique; response `201` `{ message, category }`.
- `PUT /api/admin/content/categories/{id}`: body `name` required string max 255 unique ignoring current id; response `{ message, category }`.
- `DELETE /api/admin/content/categories/{id}`: response `{ message }`; returns `422` if linked to services/projects.
- `GET /api/admin/wallets`: response `{ status, wallets }`.
- `GET /api/admin/transactions`: response `{ status, wallet }` for admin wallet.
- `GET /api/admin/escrow/transactions`: response `{ status, wallet }` for escrow wallet.
- `GET /api/admin/earnings`: response `{ status, balance, earnings }`.
- `GET /api/admin/settings`: response `{ settings }`.
- `PUT /api/admin/settings`: body `critical_dispute_notifications` required boolean, `company_verification_notifications` required boolean; response `{ message, settings }`.
- `GET /api/reports`: response `{ reports }`.
- `PUT /api/reports/{id}/decision`: body `status` required enum `accepted|rejected`, `admin_decision` nullable string, `admin_action` nullable enum `refund_client|release_freelancer`; response `{ message, report }`.

## Models and Database Fields

Field types are derived from migrations. Required means non-null in DB or required by create validation.

| Model | Table | Important fields | Relationships |
|---|---|---|---|
| `User` | `users` | `id`, `name` string required, `email` unique required, `email_verified_at` nullable timestamp, `role` enum `personal|company|admin`, `status` enum `unactive|pending_review|under_review|active|blocked`, `password`, timestamps | hasOne profile, company, wallet, settings; hasMany contracts/reviews |
| `Profile` | `profiles` | `id`, `user_id` unique FK required, `job_title` nullable string, `description` nullable text, `address` nullable string, `bio` nullable text, `phone` nullable string, `governorate_id` nullable FK, `city_id` nullable FK | belongsTo user/governorate/city; belongsToMany skills |
| `Company` | `companies` | `id`, `user_id` unique FK required, `company_name` required string, `logo` nullable string, `website` nullable string, `location` nullable string, `phone` nullable string, `description` nullable text, `is_verified` boolean default false, `governorate_id` nullable FK, `city_id` nullable FK | belongsTo user/governorate/city; hasMany jobPosts; belongsToMany skills |
| `Skill` | `skills` | `id`, `name` unique string required | belongsToMany profiles, companies, projects |
| `Category` | `categories` | `id`, `name` string required | hasMany services |
| `Governorate` | `governorates` | `id`, `name` string required | hasMany cities |
| `City` | `cities` | `id`, `governorate_id` FK required, `name` string required, unique governorate/name | belongsTo governorate |
| `Service` | `services` | `id`, `user_id` FK required, `category_id` FK required, `title` required, `description` nullable, `price` decimal(10,2), `delivery_days` int, `status` string default active | belongsTo user/category; hasMany requests |
| `UserProject` | `user_projects` | `id`, `user_id` FK required, `category_id` FK required, `governorate_id` nullable FK, `city_id` nullable FK, `title` required, `description` required, `budget` decimal(10,2), `duration_days` int, `status` string default active | belongsTo user/category/governorate/city; belongsToMany skills; hasMany applications |
| `JobPost` | `job_posts` | `id`, `company_id` FK required, `title` required, `description` required, `location_type` enum `remote|on_site|hybrid` nullable, `city_id` nullable FK, `salary` decimal(12,2) nullable, `status` enum `active|paused|closed` | belongsTo company/city |
| `Application` | `applications` | `id`, `user_project_id` FK required, `user_id` FK required, `price` decimal(10,2), `duration_days` int, `description` text, `status` enum `pending|accepted|rejected`, unique project/user | belongsTo project/user |
| `ServiceRequest` | `service_requests` | `id`, `service_id` FK required, `client_id` FK required, `title`, `description`, `references` nullable text, `delivery_days`, `status` enum `pending|accepted|rejected` | belongsTo service/client |
| `Contract` | `contracts` | `id`, `client_id` FK, `freelancer_id` FK, `user_project_id` nullable FK, `service_request_id` nullable FK, `job_post_id` nullable FK, `application_id` nullable FK, `amount` decimal(15,2), `commission_amount`, `freelancer_amount`, `status` string default pending, `funded_at` nullable, `completed_at` nullable | belongsTo client/freelancer/project/serviceRequest/jobPost/application; hasMany reviews |
| `Review` | `reviews` | `id`, `contract_id` FK, `reviewer_id` FK, `reviewed_user_id` FK, `rating` unsigned tiny int, `comment` nullable, unique contract/reviewer/reviewed | belongsTo contract/reviewer/reviewedUser |
| `Report` | `reports` | `id`, `reporter_id` FK, `target_type` string, `target_id` unsigned bigint, `contract_id` nullable, `title` nullable, `category` string default complaint/support, `priority` string default normal, `description` text, `attachments` json nullable, `status` string default pending, `admin_decision` nullable | belongsTo reporter/contract |
| `Wallet` | `wallets` | `id`, `user_id` nullable unique FK, `type` string default user, `balance` decimal(15,2), `is_active` boolean | belongsTo user; hasMany transactions |
| `WalletTransaction` | `wallet_transactions` | `id`, `wallet_id` FK, `user_id` nullable FK, `type` string, `direction` enum `credit|debit`, `amount`, `balance_before`, `balance_after`, `status` enum `completed|failed|pending`, `description` nullable | belongsTo wallet/user |
| `Conversation` | `conversations` | `id`, `user1_id` FK, `user2_id` FK, `last_message_at` nullable, unique user pair | hasMany messages; belongsTo user1/user2 |
| `Message` | `messages` | `id`, `conversation_id` FK, `sender_id` FK, `content` encrypted text, `type` string default text, `edited_at` nullable, `read_at` nullable | belongsTo conversation/sender |
| `UserNotification` | `user_notifications` | `id`, `user_id` FK, `type` nullable, `title`, `message`, `read_at` nullable | belongsTo user |
| `UserSetting` | `user_settings` | `id`, `user_id` unique FK, `profile_visible` boolean, `contact_permission` string default all, `message_notifications` boolean | belongsTo user |
| `AdminSetting` | `admin_settings` | `id`, `key` unique string, `value` string default 0 | none defined |
| `EmailVerificationOtp` | `email_verification_otps` | `id`, `user_id` FK, `otp` hashed string, `expires_at` timestamp | none defined |

Pivot tables:

- `profile_skill`: `profile_id`, `skill_id`, unique pair.
- `company_skill`: `company_id`, `skill_id`, unique pair.
- `project_skill`: `user_project_id`, `skill_id`, unique pair.

## File Upload Endpoints

No actual upload endpoint was found.

Evidence:

- No controller uses `$request->hasFile(...)`, `$request->file(...)`, or file validation rules.
- Company has a `logo` DB field, but no endpoint currently uploads or updates it.
- Reports accept `attachments` as an array of strings, not uploaded files.
- Conversation messages allow `type=image|file`, but still accept only text `content`.

## Pagination / Search / Filters

Pagination:

- `GET /api/company/services`: `paginate(9)`.
- `GET /api/company/contracts`: `paginate(10)`.
- `GET /api/conversations`: `paginate(20)`.
- `GET /api/conversations/{conversation}/messages`: `paginate(20)`.
- `GET /api/notifications`: `paginate(20)`.
- `GET /api/reports/my`: `paginate(10)`.
- `GET /api/admin/users`: `paginate(10)`.
- `GET /api/admin/companies`: `paginate(10)`.
- `GET /api/admin/content/projects`: `paginate(10)`.
- `GET /api/admin/content/services`: `paginate(10)`.
- `GET /api/admin/content/jobs`: `paginate(10)`.

Search/filter support:

- `GET /api/company/services`: `search`, `category_id`.
- `GET /api/projects`: `governorate_id`, `city_id`.
- `GET /api/jobs`: `city_id`, `governorate_id`.
- `GET /api/cities`: `governorate_id`.
- `GET /api/governorates`: `with_cities`.
- `GET /api/admin/users`: `search`, `role`, `status`.
- `GET /api/admin/companies`: `search`, `is_verified`.
- `GET /api/admin/content/projects`: `search`, `status`, `category_id`, `governorate_id`, `city_id`.
- `GET /api/admin/content/services`: `search`, `status`, `category_id`.
- `GET /api/admin/content/jobs`: `search`, `status`, `governorate_id`, `city_id`.

Laravel paginator response shape appears under the named key, for example:

```json
{
  "services": {
    "current_page": 1,
    "data": [],
    "first_page_url": "...",
    "from": null,
    "last_page": 1,
    "last_page_url": "...",
    "links": [],
    "next_page_url": null,
    "path": "...",
    "per_page": 9,
    "prev_page_url": null,
    "to": null,
    "total": 0
  }
}
```

## Error Response Format

Explicit controller errors mostly return:

```json
{
  "message": "string"
}
```

Laravel validation errors return:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["message"]
  }
}
```

Sanctum unauthenticated response is expected as:

```json
{
  "message": "Unauthenticated."
}
```

Custom role middleware returns:

```json
{
  "message": "Unauthorized"
}
```

Model not found / route model binding errors are Laravel defaults, usually `404`.

## CORS Notes

- No `config/cors.php` file was found in the current project tree.
- No `FRONTEND_URL` or `CORS_ALLOWED_ORIGINS` variable was found in `.env`.
- `config/sanctum.php` includes default `SANCTUM_STATEFUL_DOMAINS`, but the current auth flow issues Bearer tokens, so the frontend can authenticate with `Authorization: Bearer <token>`.
- For browser frontend integration, CORS may still need explicit configuration depending on Laravel 12 middleware defaults and deployment setup.
- Place to check/add configuration later if needed: Laravel bootstrap middleware configuration in `bootstrap/app.php` and/or publish/add `config/cors.php`. No change was made.

## Backend Readiness For Frontend Integration

Ready/usable:

- Auth register/login/logout/email OTP/password reset token flow exists.
- Bearer token auth with Sanctum exists.
- Role gates exist for personal/company/admin.
- Core browse endpoints exist for services, projects, jobs, categories, and locations.
- CRUD-like flows exist for services, projects, jobs, user/company profiles.
- Applications, service requests, contracts, wallet, reviews, reports, notifications, conversations, and admin management exist.
- Many list endpoints return clear JSON wrappers.

Needs frontend attention:

- Some list endpoints are not paginated (`services`, `projects`, `jobs`, many received/my lists).
- Error message text in source files appears mojibake/encoding-corrupted, though JSON keys are usable.
- File uploads are not implemented despite `logo`, `attachments`, and message `image|file` concepts.
- CORS/frontend origin variables are not configured in `.env`.
- `APP_KEY` is empty in current `.env`; this will break encryption-dependent features until generated.
- Company account approval flow blocks login until admin verifies/activates.

## Missing Or Unclear Backend Parts

- No API Resource classes, OpenAPI spec, or formal response schemas found; response shapes are inferred from controllers and Eloquent serialization.
- No dedicated request validation classes found.
- No explicit CORS config found.
- No upload/storage endpoints found.
- No explicit frontend URL or allowed origins env vars found.
- No tests covering these API contracts found beyond default example tests.
- `Profile` model fillable does not include `name`, but registration calls `$user->profile()->create(['name' => $user->name])`; the `profiles` migration also has no `name` column. This may cause profile name not to persist or could fail depending on DB/schema state.
- `UserNotification` migration `down()` drops `notifications` while `up()` creates `user_notifications`; rollback mismatch.
- `JobPostController@show` does not filter status, unlike `index`.
- `ReportController@store` has both `target_type=contract` with `target_id` and separate `contract_id`, so the frontend should prefer `target_type=contract` + `target_id=<contract id>` for disputes unless backend clarifies otherwise.

