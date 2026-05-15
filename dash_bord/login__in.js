


// نستخدم fetch بدلاً من require
async function loginUser(event) {
     event.preventDefault(); // منع تحديث الصفحة
    const name = document.getElementById('name').value;
    const password = document.getElementById('password').value;
  
    try {
        const response = await fetch('http://localhost:3000/login', {
            method: 'post',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, password })
        });

        const result = await response.json();

        
        if (result.success) {
            alert(result.message);
            console.log(result)
            window.location.href = "http://localhost/store_miniproject/dash_bord/main.html";
        }
         else {
            alert(result.message);
        }

    } 
       catch (error) {
        console.error("خطأ في الاتصال بالسيرفر:", error);
        alert('خطأ في الاتصال بالسيرفر');
    }
       
    } 
 


// إضافة event listener عند تحميل الصفحة
// document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', loginUser);
    }
// });

