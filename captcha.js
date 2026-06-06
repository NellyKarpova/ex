let currentOrder = [1, 2, 3, 4];
let selectedIndex = null;
let captchaFails = 0;

function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function renderCaptcha() {
    const container = document.getElementById('captchaContainer');
    if (!container) return;
    container.innerHTML = '';
    currentOrder.forEach((num, idx) => {
        const img = document.createElement('img');
        img.src = `captcha_images/${num}.png`;
        img.className = 'captcha-img';
        if (selectedIndex === idx) img.classList.add('selected');
        img.addEventListener('click', () => {
            if (selectedIndex === null) {
                selectedIndex = idx;
            } else {
                [currentOrder[selectedIndex], currentOrder[idx]] = [currentOrder[idx], currentOrder[selectedIndex]];
                selectedIndex = null;
                renderCaptcha();
            }
        });
        container.appendChild(img);
    });
}

function checkCaptcha() {
    if (currentOrder[0] === 1 && currentOrder[1] === 2 && currentOrder[2] === 3 && currentOrder[3] === 4) {
        document.getElementById('captchaOk').value = '1';
        document.getElementById('captchaError').innerText = '';
        document.getElementById('loginBtn').disabled = false;
        return true;
    } else {
        captchaFails++;
        document.getElementById('captchaError').innerText = `Пазл собран неверно. Осталось попыток: ${3 - captchaFails}`;
        if (captchaFails >= 3) {
            document.getElementById('captchaError').innerText = 'Вы исчерпали попытки сборки пазла. Перезагрузите страницу.';
            document.getElementById('loginBtn').disabled = true;
        }
        document.getElementById('captchaOk').value = '';
        return false;
    }
}

function resetCaptcha() {
    currentOrder = shuffleArray([1, 2, 3, 4]);
    selectedIndex = null;
    renderCaptcha();
    document.getElementById('captchaOk').value = '';
    document.getElementById('loginBtn').disabled = true;
    captchaFails = 0;
    document.getElementById('captchaError').innerText = '';
}

window.onload = () => {
    currentOrder = shuffleArray([1, 2, 3, 4]);
    renderCaptcha();
    const checkBtn = document.getElementById('checkCaptchaBtn');
    const resetBtn = document.getElementById('resetCaptchaBtn');
    if (checkBtn) checkBtn.addEventListener('click', checkCaptcha);
    if (resetBtn) resetBtn.addEventListener('click', resetCaptcha);
};