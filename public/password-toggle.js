(function () {
    const eyeIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M12 5.5c-5.2 0-8.7 4.7-9.7 6.3a.9.9 0 0 0 0 .9c1 1.6 4.5 6.3 9.7 6.3s8.7-4.7 9.7-6.3a.9.9 0 0 0 0-.9c-1-1.6-4.5-6.3-9.7-6.3Zm0 11.3a4.8 4.8 0 1 1 0-9.6 4.8 4.8 0 0 1 0 9.6Zm0-2a2.8 2.8 0 1 0 0-5.6 2.8 2.8 0 0 0 0 5.6Z"></path>
        </svg>`;

    const eyeOffIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4.3 3.1 21 19.8l-1.3 1.3-3.1-3.1A10.7 10.7 0 0 1 12 19c-5.2 0-8.7-4.7-9.7-6.3a.9.9 0 0 1 0-.9 18 18 0 0 1 4-4.4L3 4.4l1.3-1.3Zm5.1 7.6a2.8 2.8 0 0 0 3.9 3.9l-3.9-3.9ZM12 5.5c5.2 0 8.7 4.7 9.7 6.3a.9.9 0 0 1 0 .9 17.4 17.4 0 0 1-2.5 3.2l-2.8-2.8a4.8 4.8 0 0 0-5.5-5.5L8.8 5.7c1-.2 2.1-.2 3.2-.2Z"></path>
        </svg>`;

    const setPasswordVisibility = (input, visible) => {
        input.type = visible ? 'text' : 'password';
        const button = input.parentElement?.querySelector('[data-password-toggle]');
        if (button) {
            button.dataset.visible = visible ? '1' : '0';
            button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
            button.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
            button.innerHTML = visible ? eyeOffIcon : eyeIcon;
        }
    };

    const initPasswordToggles = (root = document) => {
        root.querySelectorAll('input[type="password"]').forEach((input) => {
            if (input.dataset.passwordToggleBound === '1') return;
            input.dataset.passwordToggleBound = '1';

            let wrapper = input.closest('.password-field');
            if (!wrapper) {
                wrapper = document.createElement('div');
                wrapper.className = 'password-field';
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'password-toggle';
            button.dataset.passwordToggle = '1';
            button.dataset.visible = '0';
            button.setAttribute('aria-label', 'Mostrar contraseña');
            button.title = 'Mostrar contraseña';
            button.innerHTML = eyeIcon;

            button.addEventListener('click', () => {
                const visible = input.type === 'password';
                setPasswordVisibility(input, visible);
                input.focus({preventScroll: true});
                const length = input.value.length;
                if (input.setSelectionRange) {
                    input.setSelectionRange(length, length);
                }
            });

            wrapper.appendChild(button);
        });
    };

    window.initPasswordToggles = initPasswordToggles;
    window.setPasswordVisibility = setPasswordVisibility;

    document.addEventListener('DOMContentLoaded', () => initPasswordToggles());
})();
