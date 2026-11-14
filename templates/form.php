<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сборка файлов проекта</title>
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="header" class="center">Сборка файлов проекта</div>

<div id="content">

    <div id="head">
        <div id="title" class="center">Сборка содержимого файлов в архивы по размеру</div>
    </div>

    <div id="builder">

        <form id="build-form" autocomplete="off">

            <div class="field">
                <label for="source_dir">Исходная папка</label>
                <input type="text" id="source_dir" name="source_dir"
                       placeholder="/path/to/project"
                       value="<?= htmlspecialchars($user['source_dir'], ENT_QUOTES) ?>" required>
            </div>

            <div class="field">
                <label for="output_dir">Выходная папка (создастся автоматически, если её нет)</label>
                <input type="text" id="output_dir" name="output_dir"
                       placeholder="/path/to/output"
                       value="<?= htmlspecialchars($user['output_dir'], ENT_QUOTES) ?>" required>
            </div>

            <div class="field">
                <label for="extensions">Типы файлов (мультивыбор)</label>
                <select id="extensions" name="extensions[]" multiple size="6" required>
                    <?php foreach ($config['allowed_extensions_default'] as $ext): ?>
                        <option value="<?= htmlspecialchars($ext) ?>"
                            <?= in_array($ext, $user['extensions'], true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ext) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="limit_kb">Максимальный размер одного выходного файла (КБ)</label>
                <input type="number" id="limit_kb" name="limit_kb"
                       min="1" step="1"
                       value="<?= (int)$user['limit_kb'] ?>" required>
            </div>

            <button type="submit" id="submit-btn" class="butt">Начать сборку</button>

        </form>

        <section id="status" class="status" hidden></section>

    </div>

</div>

<script src="js/app.js"></script>
</body>
</html>