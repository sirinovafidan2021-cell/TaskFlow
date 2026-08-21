# Frontend

Frontend Blade, Tailwind CSS və Vite-dən ibarətdir. resources/css/app.css Tailwind 4 import edir. resources/js/app.js mövcuddur, lakin hazırda tətbiq JavaScript məntiqi yoxdur. Vite build/dev üçün package.json-da npm run build və npm run dev script-ləri var.

## Səhifələr və istifadəçi axını

Login Blade səhifəsi GET /login-də email, parol və remember checkbox təqdim edir. Register səhifəsi GET /register-də name, email, password və confirmation təqdim edir. Qeydiyyatdan sonra user verify-email səhifəsinə keçir, email linkini təsdiqləyir və bundan sonra home/dashboard-a daxil ola bilir.

Axın: Login və ya Register → Email verification → Login edilmiş verified user → Dashboard → Projects → Tasks → Activity.

Modules/Projects/resources/views layihə siyahısı, forma, detal və üzv səhifələrini; Modules/Tasks/resources/views task siyahısı, forma və detalı; Activity və Dashboard modulları index Blade görünüşlərini saxlayır. Layout resources/views/layouts/app.blade.php faylındadır.
