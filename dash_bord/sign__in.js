// نستخدم fetch بدلاً من require
async function signUser(event) {
    event.preventDefault(); // منع تحديث الصفحة
    const name = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value;
    const message= document.getElementById('message');

    try {
        const response = await fetch('http://localhost:3000/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        });

        const result = await response.json();
        if (!result.success) {
             message.textContent = result.message;
             message.style.color = "red";
             document.getElementById('signForm').reset();
                              }
       else {

    message.textContent = result.message;
    message.style.color = "green";
          }
    } 
    
    
    catch (error) {
        console.error("خطأ في الاتصال بالسيرفر:", error);
        alert('خطأ في الاتصال بالسيرفر');
    }
}

const signForm = document.getElementById('signForm');
if (signForm) {
    
    signForm.addEventListener('submit', signUser);
} else {
    console.warn('signForm not found');
}


