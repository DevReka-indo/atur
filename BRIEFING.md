==================================================
PROJECT CONTEXT
===============

Nama Project:
- ATUR (dikonfirmasi dari `APP_NAME=ATUR` pada environment pengguna dan nama repository `atur`).

Tujuan Project:
- Aplikasi web manajemen workspace, project, task, progress, diskusi, notifikasi, invitation, dan manajemen user.
- Fokus bisnisnya adalah kolaborasi project: user bergabung ke workspace, membuat project, mengatur anggota, membuat task bertingkat, memantau status/progress/S-curve, berkomentar, upload attachment, berdiskusi per project/thread, dan menerima notifikasi deadline/overload.

Tech Stack:
- Backend: PHP ^8.2, Laravel 12 (dikonfirmasi pengguna; catatan: metadata dependency di `composer.json` perlu diselaraskan bila masih mencantumkan versi Laravel berbeda).
- Auth scaffold: Laravel Breeze.
- OAuth: Laravel Socialite untuk Google login.
- Frontend: Blade, Tailwind CSS 3, Alpine.js, Vite 7, Axios.
- Queue: Laravel queue dengan job `SendEmailNotification`.
- Database: MySQL untuk environment pengguna (`DB_CONNECTION=mysql`, database lokal `db_atur_magang`); schema didefinisikan lewat Laravel migrations.
- Testing: PHPUnit 11, Laravel test runner.
- Dev tooling: Laravel Pint, Sail, Pail, Tinker, Concurrently.

Arsitektur:
- Monolith Laravel MVC berbasis server-rendered Blade.
- Entry route utama ada di `routes/web.php` dan route auth Breeze di `routes/auth.php`.
- Domain utama direpresentasikan oleh Eloquent Models di `app/Models`.
- Controller menangani validasi request, authorization manual, manipulasi Eloquent, redirect/view, dan sebagian notifikasi.
- Service layer terbatas pada `ProjectProgressService`, `ActivityLogService`, dan `SsoTokenService`.
- Tidak ditemukan repository pattern formal.
- Authorization sebagian besar manual lewat pengecekan role/membership di controller/model; tidak ditemukan Policy/Gate khusus.
- Identitas resource publik memakai token acak 32 karakter untuk workspace/project/task, bukan id numerik pada route show/edit/update/destroy.

Daftar Fitur:
1. Authentication
   - Login, register, logout, reset password, email verification, confirm password dari Breeze.
   - Google OAuth via `/auth/google` dan `/auth/google/callback`.
   - SSO eksternal via `/login/sso`, `/sso/redirect`, dan `/sso/callback`.
   - Multi-account per device via `device_users`, switch account, remove account from device.

2. Dashboard
   - Ringkasan workspace, project, task, progress, deadline, dan notifikasi.
   - Live search untuk workspace/project/task yang dapat diakses user.
   - Activity log, halaman account, halaman about, overload list.

3. Workspace Management
   - CRUD workspace.
   - Membership workspace dengan role owner/admin/member.
   - Invite link workspace: generate/reset/join/accept/decline.
   - Cascade remove member dari workspace dan project/task terkait.
   - Management page untuk super admin/admin.

4. Project Management
   - CRUD project dalam workspace.
   - Project members dengan role manager/member/viewer.
   - Status project: planning, active, on_hold, completed, cancelled, urgent.
   - Progress otomatis dari task weight dan status weight.
   - Baseline/planned progress/actual progress untuk kurva rencana vs aktual.
   - Endpoint data Gantt project.

5. Task Management
   - CRUD task.
   - Task bisa punya parent/subtask.
   - Multi-assignee via `task_assignees`, tetapi masih ada field legacy `assignee_id`.
   - Status task berbasis tabel `task_status_weights`.
   - Priority: low, medium, high, urgent.
   - Dependency task: predecessor + dependency_type FS/SS/FF/SF.
   - Stopped progress untuk status on_hold.
   - Comment, attachment, download attachment.
   - Status history, mark seen, Gantt data, task JSON dan assignee JSON.

6. Discussion
   - Daftar project yang dapat didiskusikan.
   - Thread per project.
   - Message per thread.
   - Unread counts dan read tracking dengan `thread_user_reads`.
   - Create/update/delete thread dan message.

7. Notification
   - Tabel `notifications` untuk user notification.
   - Polling notification.
   - Mark as read/read all/delete.
   - Email notification asynchronous via job queue.
   - Notifikasi deadline task dan overload member.

8. Invitation
   - Undangan workspace/project via email dan token.
   - Accept, join, reject, decline.
   - Invite link workspace publik.

9. User/Profile Management
   - Profile update, password update, upload/delete profile photo.
   - CRUD management user.
   - Toggle active/inactive user.
   - Role global user: super_admin/member (role lain mungkin dipakai secara asumtif di controller/UI, cek implementasi sebelum mengubah).

Role:
- Global user role:
  - `super_admin`: akses manajemen user/workspace/project lebih luas.
  - `member`: default user biasa.
- Workspace role:
  - `owner`: creator workspace; dapat manage settings/members/create project.
  - `admin`: dapat manage members dan create project.
  - `member`: akses sebagai anggota.
- Project role:
  - `manager`: pengelola project/member/task.
  - `member`: anggota project.
  - `viewer`: akses lihat terbatas.
- Task role/akses:
  - Tidak ada role task formal; akses berdasarkan creator, assignees, project/workspace membership, dan logic controller.

Database:
- `users`: user auth; kolom tambahan profile_photo, role, last_activity, job_title, department, has_password, google_id, sso_id, employee_id, is_active.
- `password_reset_tokens`, `sessions`: tabel auth/session Laravel.
- `cache`, `cache_locks`: cache Laravel.
- `jobs`, `job_batches`, `failed_jobs`: queue Laravel.
- `workspaces`: name, description, created_by, invite_token, token.
- `workspace_members`: workspace_id, user_id, role, joined_at, invited_by, status, timestamps.
- `projects`: workspace_id, name, description, status, start_date, end_date, created_by, token.
- `project_members`: project_id, user_id, role, joined_at, timestamps.
- `task_status_weights`: status, weight_value, description. Dipakai menghitung earned value/progress.
- `tasks`: project_id, parent_task_id, name, description, assignee_id legacy, status, priority, weight, start_date, due_date, position, completed_at, stopped_progress, created_by, token, predecessor_id, dependency_type.
- `task_assignees`: pivot many-to-many task-user.
- `task_status_history`: task_id, from_status, to_status, changed_by, changed_at.
- `task_comments`: task_id, user_id, comment.
- `task_attachments`: task_id, uploaded_by, file_name, file_path, file_size, mime_type, created_at.
- `project_baselines`: project_id, baseline_name, is_active, created_by.
- `planned_progress`: baseline_id, date, planned_cumulative_percentage.
- `actual_progress`: project_id, baseline_id, date, actual_cumulative_percentage, completed_tasks_count, total_tasks_count, notes, created_by.
- `task_baselines`: project_baseline_id, task_id, baseline_start, baseline_end.
- `activity_logs`: user_id, action, entity_type, entity_id, description, old_value, new_value, timestamps; entity_type sudah ditambah `discussion` via migration, tetapi model mapping belum memasukkan discussion.
- `invitations`: email, token, type workspace/project, invitable_id, invited_by, status pending/accepted/expired, expires_at.
- `device_users`: device_id, user_id.
- `notifications`: user_id, type, title, message, task_id, project_id, read_at.
- `project_threads`: project_id, user_id, title, body.
- `project_thread_messages`: project_thread_id, user_id, content.
- `thread_user_reads`: thread_id, user_id, last_read_at.

Relasi Model Penting:
- User has many createdWorkspaces, createdProjects, createdTasks, comments, attachments, activityLogs, createdBaselines, recordedProgress.
- User belongsToMany Workspace via `workspace_members` dan Project via `project_members`.
- Workspace belongsTo creator User, belongsToMany members, hasMany projects.
- Project belongsTo Workspace/creator, belongsToMany members, hasMany tasks/baselines/progress/threads.
- Task belongsTo Project, parent Task, assignee legacy User, creator User; hasMany subtasks/comments/attachments/statusHistory; belongsToMany assignees.
- ProjectBaseline hasMany plannedProgress, actualProgress, taskBaselines.
- Discussion: Project hasMany ProjectThread; thread hasMany ProjectThreadMessage dan ThreadUserRead.

Alur Bisnis:
1. Workspace
   - User login membuat workspace.
   - Creator otomatis menjadi owner secara konseptual (`created_by`).
   - Owner/admin menambah anggota langsung atau membuat invite link.
   - Workspace menjadi container untuk project.

2. Project
   - Owner/admin workspace membuat project dan memilih workspace.
   - Creator/manager menambahkan project members.
   - Project memiliki status, tanggal mulai/akhir, dan token publik untuk route.
   - Saat project/task berubah, `ProjectProgressService` menyinkronkan planned progress dan actual progress.

3. Task
   - User membuat task pada project yang dapat diakses.
   - Task dapat diberi assignees, parent task, priority, tanggal, weight, predecessor, dependency type.
   - Status task mempengaruhi progress project melalui `task_status_weights`.
   - Perubahan status dicatat ke `task_status_history`, mengubah `completed_at`, dan mengirim notifikasi/email pada skenario tertentu.
   - Comment dan attachment menjadi aktivitas kolaborasi task.

4. Progress/Baseline
   - Jika project belum punya baseline aktif, service membuat `Auto Baseline`.
   - Planned progress dibuat dari distribusi tanggal task dan weight; jika task kosong/weight nol, dibuat kurva default S-curve.
   - Actual progress direkam per tanggal dari `Project::calculateProgress()` dan jumlah task selesai.

5. Discussion
   - User memilih project yang ia buat atau menjadi member.
   - User membuat thread, masuk chat, kirim/edit/hapus message.
   - Read state dicatat per user-thread untuk unread counts.

6. Invitation
   - Pengundang mengirim undangan email/token untuk workspace/project atau generate invite link workspace.
   - Penerima accept/join untuk menjadi member, reject/decline untuk menolak.

Folder Penting:
- `app/Http/Controllers`: controller domain dan auth.
- `app/Models`: semua model Eloquent domain.
- `app/Services`: service progress, activity log, dan SSO.
- `app/Jobs`: queued email notification.
- `app/Mail`: mailable invitation dan notification.
- `app/Http/Requests`: form request profile dan login.
- `routes/web.php`: route aplikasi utama.
- `routes/auth.php`: route auth Breeze.
- `database/migrations`: definisi schema database.
- `resources/views`: Blade utama aplikasi.
- `resources/views/layouts`: layout app/guest/sidebar/logo.
- `resources/views/auth/components`: komponen Blade auth/Breeze.
- `resources/js`: Alpine bootstrap.
- `resources/css`: Tailwind entrypoint.
- `config/services.php`: konfigurasi Google OAuth dan SSO.
- `resources/views_backup`: backup view lama; jangan dianggap source of truth kecuali perlu referensi historis.

Coding Convention:
- Ikuti standar Laravel MVC dan Eloquent relationship.
- Route resource domain memakai token 32 karakter untuk workspace/project/task; jangan ubah menjadi id tanpa migrasi/compatibility plan.
- Gunakan validation Laravel di controller/request.
- Jangan bungkus import dengan try/catch.
- Untuk UI, gunakan Blade + Tailwind + Alpine, bukan SPA framework baru kecuali diminta.
- Saat menambah behavior progress project, panggil `ProjectProgressService::syncPlannedProgress()` dan `recordActualProgress()` setelah perubahan task/project yang mempengaruhi timeline/weight/status.
- Saat menambah aktivitas domain penting, gunakan/ikuti pola `ActivityLogService`.
- Saat menambah email notification, dispatch `SendEmailNotification` agar masuk queue.

Route Penting:
- `/`: redirect dashboard jika login, jika guest tampil login.
- Auth Breeze: `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`, `/logout`.
- Google OAuth: `/auth/google`, `/auth/google/callback`.
- SSO: `/login/sso`, `/sso/redirect`, `/sso/callback`.
- Dashboard/settings: `/dashboard`, `/live-search`, `/settings/account`, `/settings/about`, `/activity-log`, `/overload`.
- Notifications: `/settings/notifications`, `/notifications/poll`, `/notifications/read-all`, `/notifications/{id}/read`, `/notifications/{id}`.
- Workspaces: `/workspaces`, `/workspaces/create`, `/workspaces/{token}`, `/workspaces/{token}/edit`, member/invite routes.
- Projects: `/projects`, `/projects/create`, `/projects/{token}`, `/projects/{token}/edit`, `/projects/{token}/members`, `/projects/{token}/status`, `/gantt/project-data`.
- Tasks: `/tasks`, `/tasks/create`, `/tasks/{token}`, `/tasks/{token}/edit`, comments, attachments, status, Gantt, JSON helpers, mark-seen.
- Management: resource `management-users`, `/management-projects`, `/management-workspaces`.
- Discussion: `/discussion`, `/discussion/{project}`, `/discussion/{project}/{thread}`, threads/messages/unread routes.
- Profile: `/profile`, `/profile/photo`.
- Invitations: `/invitations/send`, `/invitations/join`, `/invitations/reject`, `/invitations/decline`, `/invitations/accept/{token}`, `/join/{token}`.

API/Endpoint JSON:
- Tidak ada `routes/api.php` khusus yang terdeteksi dari daftar file; API bersifat web routes yang sebagian return JSON.
- JSON/polling endpoints utama:
  - `GET /live-search`
  - `GET /notifications/poll`
  - `GET /gantt/project-data`
  - `GET /gantt/data`
  - `GET /projects/{id}/tasks-json`
  - `GET /projects/{id}/assignees-json`
  - `POST /tasks/{token}/mark-seen`
  - `GET /discussion/{project}/unread`, `/discussion/{project}/unread-counts`, `/discussion/unread-sidebar`
- Authentication endpoint memakai session auth Laravel, bukan token API.
- Token management domain: token acak di workspace/project/task/invitation/invite link, bukan bearer token.
- Integrasi pihak ketiga:
  - Google OAuth memakai env `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.
  - SSO eksternal memakai env `SSO_BASE_URL`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_CALLBACK_URL`, `SSO_AFTER_LOGIN_URL`, `SSO_LOGOUT_URL`, `SSO_AFTER_LOGOUT_URL`.
  - Mail driver mengikuti konfigurasi Laravel `.env`.

Cara Menjalankan:
- Install backend: `composer install`.
- Buat env: `cp .env.example .env` jika tersedia.
- Generate key: `php artisan key:generate`.
- Set database di `.env`, lalu jalankan `php artisan migrate`.
- Install frontend: `npm install`.
- Development full stack: `composer run dev` menjalankan server Laravel, queue listener, pail logs, dan Vite secara concurrent.
- Alternatif manual: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `npm run dev`.
- Build production asset: `npm run build`.
- Test: `composer test` atau `php artisan test`.
- Requirement server: PHP 8.2+, Composer, Node/NPM, database supported Laravel, web server, writable `storage` dan `bootstrap/cache`, queue worker untuk email async.

Environment Variable Penting:
- Identitas app: `APP_NAME=ATUR`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` (production pengguna: `https://atur.ptrekaindo.co.id/`).
- Locale/default: `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`.
- Logging: `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`.
- Database: environment pengguna memakai MySQL (`DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=db_atur_magang`). Jangan menaruh username/password database asli di dokumentasi atau repository.
- Session/cache/queue: environment pengguna memakai `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`; ini berarti migration tabel session/cache/jobs harus tersedia dan queue worker wajib berjalan untuk job email.
- Filesystem/broadcast: `FILESYSTEM_DISK=local`, `BROADCAST_CONNECTION=log`.
- Mail SMTP: environment pengguna memakai Gmail SMTP (`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`, `MAIL_FROM_ADDRESS`). Jangan commit password/app-password email.
- Google OAuth: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (redirect production menuju `/auth/google/callback`). Jangan commit client secret.
- SSO PT Rekaindo: `SSO_BASE_URL`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_CALLBACK_URL`, `SSO_AFTER_LOGIN_URL`, `SSO_LOGOUT_URL`, `SSO_AFTER_LOGOUT_URL`. Jangan commit client secret.
- Redis/Memcached/AWS tersedia sebagai konfigurasi opsional tetapi pada environment yang diberikan tidak menjadi storage utama aplikasi.
- Vite: `VITE_APP_NAME="${APP_NAME}"` dan variabel `VITE_*` lain jika dibutuhkan frontend.

Hal-hal yang Harus Diperhatikan:
- Banyak authorization dilakukan manual; selalu cek membership dan role sebelum expose data atau mutate data.
- Jangan menghapus/merusak token route karena URL bergantung pada token.
- `resources/views_backup` adalah backup, bukan view aktif.
- Task assignment punya dua mekanisme: legacy `assignee_id` dan pivot `task_assignees`; pahami keduanya sebelum refactor.
- Progress project sangat bergantung pada weight task dan status weight.
- Queue harus berjalan agar email notification dikirim.
- File upload attachment bergantung pada disk storage Laravel; pastikan storage link/config benar di deployment.
- Discussion route menggunakan implicit binding `Project $project` dan `ProjectThread $thread` berbasis id, berbeda dari project/task/workspace route utama yang berbasis token.

Known Issues:
- `composer.json` masih metadata default Laravel dan perlu diselaraskan dengan identitas project ATUR serta versi Laravel 13 yang dikonfirmasi pengguna.
- Duplikasi route `GET /projects/{id}/tasks-json` muncul dua kali di `routes/web.php`.
- `User` mendefinisikan casts dengan method `casts()` dan properti `$casts`; properti dapat membingungkan/menimpa ekspektasi casting di Laravel modern.
- Model `ActivityLog` mapping entity belum memasukkan `discussion`, padahal migration menambahkan enum discussion.
- `ActivityLog::getEntityUrlAttribute()` melakukan query `find()` berulang sehingga rawan N+1/inefisien.
- `Task::user()` belongsTo tanpa foreign key eksplisit tampak ambigu karena tabel tasks tidak jelas punya `user_id`; model sudah punya `creator()` dan `assignee()`.
- `UserController` memiliki method `handle(Request $request, Closure $next)` yang terlihat seperti middleware tetapi berada di controller; ini perlu dicek apakah unused/dead code.
- Ada migration timestamp project_members/workspace_members yang tampak duplikatif pada tanggal berbeda; hati-hati ketika menjalankan migration dari awal.
- Authorization belum tersentralisasi di Policy/Gate sehingga logic akses bisa inkonsisten antar controller.

Technical Debt:
- Controller gemuk: DashboardController, TaskController, ProjectController, WorkspaceController menampung business logic, notification logic, dan authorization sekaligus.
- Perlu ekstraksi service untuk membership, notification, invitation, overload, dan task workflow.
- Perlu Policy/Gate untuk Workspace, Project, Task, Discussion, User management.
- Perlu konsolidasi assignment task (`assignee_id` vs `task_assignees`).
- Perlu konsolidasi status/role/priority menjadi enum PHP atau constants terpusat.
- Perlu test feature untuk business flows utama; test saat ini sebagian besar bawaan Breeze/example.
- Perlu dokumentasi API JSON dan response shape jika dipakai frontend secara intensif.
- Perlu memperbaiki metadata project dan README agar sesuai aplikasi ATUR dan Laravel 13.

Panduan Jika Akan Menambah Fitur:
1. Identifikasi modul domain: workspace, project, task, discussion, notification, invitation, user/profile.
2. Tambahkan migration/model relationship jika butuh data baru.
3. Tambahkan route di `routes/web.php` dalam middleware auth kecuali fitur memang publik.
4. Implementasikan authorization secara eksplisit; idealnya buat Policy/Gate baru bila fitur sensitif.
5. Gunakan token untuk URL resource workspace/project/task jika resource tersebut publik di UI.
6. Untuk UI, buat Blade di `resources/views/<module>` dan ikuti layout `resources/views/layouts/app.blade.php` serta sidebar.
7. Jika fitur mempengaruhi task/project progress, update `ProjectProgressService` atau panggil service setelah perubahan.
8. Jika fitur mengirim email, gunakan job queue `SendEmailNotification` dan pastikan queue worker berjalan.
9. Tambahkan activity log untuk aksi penting.
10. Tambahkan test feature untuk happy path, authorization denial, dan edge cases.

Panduan Jika Akan Memperbaiki Bug:
1. Reproduksi lewat route/controller terkait dan cek view aktif di `resources/views`, bukan `resources/views_backup`.
2. Cek apakah bug terkait token-vs-id route binding.
3. Cek membership/role karena banyak data difilter manual berdasarkan user saat ini.
4. Untuk bug progress, periksa task weight, task status, `task_status_weights`, baseline aktif, planned progress, dan actual progress.
5. Untuk bug notifikasi/email, cek tabel `notifications`, dispatch job, queue connection, dan mailable/view email.
6. Untuk bug discussion unread, cek `thread_user_reads` dan urutan route discussion karena route dinamis dapat bertabrakan.
7. Untuk bug attachment, cek filesystem disk, storage symlink, path file, dan authorization download.
8. Setelah fix, jalankan minimal `php artisan test`; untuk frontend asset jalankan `npm run build`.

Referensi Source Code Utama:
- Routes: `routes/web.php`, `routes/auth.php`, `routes/console.php`.
- Models: `app/Models/*.php`.
- Controllers: `app/Http/Controllers/*.php`, `app/Http/Controllers/Auth/*.php`.
- Services: `app/Services/ProjectProgressService.php`, `app/Services/ActivityLogService.php`, `app/Services/SsoTokenService.php`.
- Jobs/Mail: `app/Jobs/SendEmailNotification.php`, `app/Mail/InvitationMail.php`, `app/Mail/NotificationMail.php`.
- Views: `resources/views/**/*.blade.php`.
- Config: `composer.json`, `package.json`, `config/services.php`, `config/auth.php`, `config/database.php`, `config/queue.php`, `vite.config.js`, `tailwind.config.js`.
- Database: `database/migrations/*.php`.

==================================================
AI INSTRUCTION
==============

Anda adalah Senior Software Engineer yang bertugas membantu pengembangan project ini. Gunakan seluruh konteks di atas sebelum memberikan jawaban, saran, refactor, atau implementasi kode.

Aturan kerja untuk AI:
- Selalu perlakukan project ini sebagai aplikasi Laravel monolith MVC berbasis Blade/Tailwind/Alpine.
- Jangan menyarankan rewrite besar ke SPA/microservice kecuali diminta eksplisit.
- Selalu cek authorization/membership/role ketika menambah atau mengubah fitur.
- Selalu pertimbangkan dampak perubahan terhadap progress project, baseline, planned progress, actual progress, notifikasi, dan activity log.
- Jika membutuhkan URL resource workspace/project/task, gunakan token existing.
- Jika ada informasi yang tidak pasti dari briefing ini, tandai sebagai asumsi dan minta/cek source code terkait.
- Saat memberi instruksi coding, sebutkan file yang kemungkinan perlu diubah.
- Saat membuat kode baru, ikuti convention Laravel, Eloquent relationship, Blade component/layout yang sudah ada, dan Tailwind CSS.
- Saat memperbaiki bug, prioritaskan patch kecil yang aman, lalu rekomendasikan refactor terpisah untuk technical debt.
