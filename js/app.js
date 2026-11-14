(function () {
    'use strict';

    const form    = document.getElementById('build-form');
    const status  = document.getElementById('status');
    const btn     = document.getElementById('submit-btn');

    function showStatus(text, kind) {
        status.hidden = false;
        status.className = 'status ' + (kind || '');
        status.textContent = text;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const data = new FormData(form);
        data.append('action', 'build');

        // Валидация выбранных расширений
        const selected = Array.from(document.getElementById('extensions').selectedOptions)
            .map(o => o.value);
        if (selected.length === 0) {
            showStatus('Выберите хотя бы один тип файлов.', 'error');
            return;
        }

        btn.disabled = true;
        showStatus('Идёт обработка, подождите…', 'pending');

        try {
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: data
            });

            const raw = await response.text();
            let result;
            try {
                result = JSON.parse(raw);
            } catch (e) {
                throw new Error('Некорректный ответ сервера: ' + raw.slice(0, 200));
            }

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Ошибка обработки.');
            }

            if (result.skipped) {
                showStatus(
                    'Готово. ' + result.skipped +
                    (result.config_saved === false ? '\n(настройки сохранить не удалось: ' + result.config_warning + ')' : ''),
                    result.config_saved === false ? 'warn' : 'warn'
                );
                return;
            }

            const suffix = result.config_saved === false
                ? '\n\n⚠ Настройки сохранить не удалось: ' + result.config_warning
                : '\n\n✔ Введённые настройки сохранены в config.json';

            showStatus(
                'Готово!\n' +
                'Обработано исходных файлов: ' + result.files + '\n' +
                'Создано выходных файлов: ' + result.count +
                suffix,
                result.config_saved === false ? 'warn' : 'success',
                result.written
            );
        } catch (err) {
            showStatus('Ошибка: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });
})();