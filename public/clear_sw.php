<?php
// clear_sw.php - Unregister all PWA Service Workers locally
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تنظيف كاش المتصفح / Reset PWA Cache</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f8fafc;
            color: #334155;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        h2 { color: #1e3a8a; }
        .btn {
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover { background-color: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h2>جاري إلغاء كاش المتصفح المانع للدخول...</h2>
        <p>إذا كان المتصفح يعرض لك صفحة "غير متصل بالإنترنت" بشكل مستمر، سيقوم هذا السكربت بحذف ذاكرة المتصفح النشطة وإعادة توجيهك فوراً.</p>
        
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.getRegistrations().then(function(registrations) {
                        if (registrations.length === 0) {
                            alert("لم يتم العثور على كاش نشط في هذا المتصفح. سيتم توجيهك لصفحة الدخول.");
                            window.location.href = "/login";
                            return;
                        }
                        for(let registration of registrations) {
                            registration.unregister();
                        }
                        alert("تم تنظيف كاش المتصفح بنجاح! سيتم تحويلك لصفحة الدخول الآن.");
                        window.location.href = "/login";
                    }).catch(function(err) {
                        console.log("Service Worker unregistration failed: ", err);
                        window.location.href = "/login";
                    });
                } else {
                    window.location.href = "/login";
                }
            });
        </script>
    </div>
</body>
</html>
