<?php
require_once __DIR__ . '/../../includes/session_check.php';

if (empty($_SESSION['diary_add_csrf_token'])) {
    $_SESSION['diary_add_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = isset($_SESSION['diary_add_errors']) && is_array($_SESSION['diary_add_errors'])
    ? $_SESSION['diary_add_errors']
    : array();
$old = isset($_SESSION['diary_add_old']) && is_array($_SESSION['diary_add_old'])
    ? $_SESSION['diary_add_old']
    : array();

if (!isset($_SESSION['diary_upload_batches']) || !is_array($_SESSION['diary_upload_batches'])) {
    $_SESSION['diary_upload_batches'] = array();
}

$upload_batch_token = '';
$old_upload_batch_token = isset($old['upload_batch_token']) && is_string($old['upload_batch_token'])
    ? $old['upload_batch_token']
    : '';

if (
    preg_match('/\A[a-f0-9]{64}\z/D', $old_upload_batch_token) === 1
    && isset($_SESSION['diary_upload_batches'][$old_upload_batch_token])
    && is_array($_SESSION['diary_upload_batches'][$old_upload_batch_token])
) {
    $upload_batch_token = $old_upload_batch_token;
}

if ($upload_batch_token === '') {
    do {
        $upload_batch_token = bin2hex(random_bytes(32));
    } while (isset($_SESSION['diary_upload_batches'][$upload_batch_token]));

    $_SESSION['diary_upload_batches'][$upload_batch_token] = array();
}

unset($_SESSION['diary_add_errors'], $_SESSION['diary_add_old']);

function diaryFormValue($old, $key, $default = '') {
    $value = isset($old[$key]) ? $old[$key] : $default;
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$mood_options = array(
    'Happy' => '😊',
    'Calm' => '😌',
    'Neutral' => '😐',
    'Sad' => '😢',
    'Stressed' => '😣'
);
$selected_mood = isset($old['mood']) && array_key_exists($old['mood'], $mood_options)
    ? $old['mood']
    : '';
$weather_options = array(
    '' => array('emoji' => '—', 'label' => 'Not set'),
    'Sunny' => array('emoji' => '☀️', 'label' => 'Sunny'),
    'Cloudy' => array('emoji' => '☁️', 'label' => 'Cloudy'),
    'Rainy' => array('emoji' => '🌧️', 'label' => 'Rainy'),
    'Windy' => array('emoji' => '💨', 'label' => 'Windy'),
    'Stormy' => array('emoji' => '⛈️', 'label' => 'Stormy')
);
$selected_weather = isset($old['weather'])
    && array_key_exists((string) $old['weather'], $weather_options)
        ? (string) $old['weather']
        : '';
$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Journal Entry</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page">
    <main class="diary-container">
        <header class="diary-page-header">
            <p class="diary-eyebrow">Diary Journal</p>
            <h1>Add Journal Entry</h1>
            <p>Record your thoughts and how you are feeling today.</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                <p>Please correct the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="diary-form" action="add_handler.php" method="post" enctype="multipart/form-data">
            <input
                type="hidden"
                name="upload_batch_token"
                value="<?php echo htmlspecialchars($upload_batch_token, ENT_QUOTES, 'UTF-8'); ?>"
            >
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['diary_add_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
            >

            <div class="diary-form-group">
                <label for="title">Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    required
                    value="<?php echo diaryFormValue($old, 'title'); ?>"
                >
            </div>

            <div class="diary-form-group">
                <label id="diary-content-label" for="diary-content-editor">Content</label>
                <div class="diary-rich-editor" data-diary-rich-editor>
                    <div class="diary-editor-toolbar" role="toolbar" aria-label="Journal text formatting">
                        <div class="diary-editor-toolbar-row diary-editor-toolbar-row-primary">
                        <div class="diary-editor-tool-group" aria-label="Text formatting">
                            <button class="diary-editor-tool" type="button" data-editor-command="bold" aria-label="Bold" title="Bold">
                                <strong aria-hidden="true">B</strong>
                            </button>
                            <button class="diary-editor-tool" type="button" data-editor-command="italic" aria-label="Italic" title="Italic">
                                <em aria-hidden="true">I</em>
                            </button>
                            <button class="diary-editor-tool" type="button" data-editor-command="underline" aria-label="Underline" title="Underline">
                                <span class="diary-editor-underline-icon" aria-hidden="true">U</span>
                            </button>
                            <button class="diary-editor-tool" type="button" data-editor-command="strikeThrough" aria-label="Strikethrough" title="Strikethrough">
                                <span class="diary-editor-strike-icon" aria-hidden="true">S</span>
                            </button>
                        </div>

                        <div class="diary-editor-tool-group diary-editor-select-group">
                            <label class="diary-visually-hidden" for="diary-font-family">Font family</label>
                            <select id="diary-font-family" data-editor-font-family aria-label="Font family">
                                <option value="">Font</option>
                                <option value="Arial">Arial</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Courier New">Courier New</option>
                            </select>

                            <label class="diary-visually-hidden" for="diary-font-size">Font size</label>
                            <select id="diary-font-size" data-editor-font-size aria-label="Font size">
                                <option value="">Size</option>
                                <option value="12">12</option>
                                <option value="14">14</option>
                                <option value="16">16</option>
                                <option value="18">18</option>
                                <option value="20">20</option>
                                <option value="24">24</option>
                                <option value="28">28</option>
                                <option value="32">32</option>
                            </select>
                        </div>

                        <div class="diary-editor-tool-group diary-editor-color-group" aria-label="Text color">
                            <span class="diary-editor-group-label">Color</span>
                            <button class="diary-editor-color" type="button" data-editor-color="#342e28" aria-label="Dark brown text" title="Dark brown" style="--editor-color: #342e28;"></button>
                            <button class="diary-editor-color" type="button" data-editor-color="#667653" aria-label="Green text" title="Green" style="--editor-color: #667653;"></button>
                            <button class="diary-editor-color" type="button" data-editor-color="#82694f" aria-label="Warm brown text" title="Warm brown" style="--editor-color: #82694f;"></button>
                            <button class="diary-editor-color" type="button" data-editor-color="#a44743" aria-label="Red text" title="Red" style="--editor-color: #a44743;"></button>
                            <button class="diary-editor-color" type="button" data-editor-color="#315a7d" aria-label="Blue text" title="Blue" style="--editor-color: #315a7d;"></button>
                        </div>

                        <div class="diary-editor-tool-group diary-editor-highlight-group" aria-label="Highlight color">
                            <span class="diary-editor-group-label">Highlight</span>
                            <button class="diary-editor-highlight" type="button" data-editor-highlight="#fff1a8" aria-label="Yellow highlight" title="Yellow highlight" style="--editor-highlight: #fff1a8;"></button>
                            <button class="diary-editor-highlight" type="button" data-editor-highlight="#dce9ce" aria-label="Green highlight" title="Green highlight" style="--editor-highlight: #dce9ce;"></button>
                            <button class="diary-editor-highlight" type="button" data-editor-highlight="#f6d3bd" aria-label="Peach highlight" title="Peach highlight" style="--editor-highlight: #f6d3bd;"></button>
                            <button class="diary-editor-highlight" type="button" data-editor-highlight="#d8e7f3" aria-label="Blue highlight" title="Blue highlight" style="--editor-highlight: #d8e7f3;"></button>
                        </div>

                        </div>
                        <div class="diary-editor-toolbar-row diary-editor-toolbar-row-secondary">

                        <div class="diary-editor-tool-group" aria-label="Text alignment">
                            <button class="diary-editor-tool diary-editor-align-tool" type="button" data-editor-command="justifyLeft" aria-label="Align left" title="Align left">≡</button>
                            <button class="diary-editor-tool diary-editor-align-tool is-centered" type="button" data-editor-command="justifyCenter" aria-label="Align center" title="Align center">≡</button>
                            <button class="diary-editor-tool diary-editor-align-tool is-right" type="button" data-editor-command="justifyRight" aria-label="Align right" title="Align right">≡</button>
                            <button class="diary-editor-tool diary-editor-justify-tool" type="button" data-editor-command="justifyFull" aria-label="Justify" title="Justify">☰</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="Lists">
                            <button class="diary-editor-tool diary-editor-list-tool" type="button" data-editor-command="insertUnorderedList" aria-label="Bullet list" title="Bullet list">•≡</button>
                            <button class="diary-editor-tool diary-editor-list-tool" type="button" data-editor-command="insertOrderedList" aria-label="Numbered list" title="Numbered list">1≡</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="Indentation">
                            <button class="diary-editor-tool" type="button" data-editor-command="outdent" aria-label="Decrease indent" title="Decrease indent">⇤</button>
                            <button class="diary-editor-tool" type="button" data-editor-command="indent" aria-label="Increase indent" title="Increase indent">⇥</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="Block quote">
                            <button class="diary-editor-tool diary-editor-quote-tool" type="button" data-editor-command="formatBlock" data-editor-value="blockquote" aria-label="Block quote" title="Block quote">❝</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="History">
                            <button class="diary-editor-tool diary-editor-history-tool" type="button" data-editor-command="undo" aria-label="Undo" title="Undo">↶</button>
                            <button class="diary-editor-tool diary-editor-history-tool" type="button" data-editor-command="redo" aria-label="Redo" title="Redo">↷</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="Clear formatting">
                            <button class="diary-editor-tool diary-editor-clear-tool" type="button" data-editor-command="removeFormat" aria-label="Clear formatting" title="Clear formatting">T×</button>
                        </div>

                        <div class="diary-editor-tool-group" aria-label="Insert media">
                            <button class="diary-editor-tool diary-editor-image-tool" type="button" data-editor-image aria-label="Insert image" title="Insert JPG, PNG, or WebP image">▧ Image</button>
                            <input
                                type="file"
                                data-editor-image-input
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                hidden
                            >
                            <button class="diary-editor-tool diary-editor-emoji-tool" type="button" data-editor-emoji-toggle aria-expanded="false" aria-controls="diary-emoji-picker" aria-label="Insert emoji" title="Insert emoji">☺ Emoji</button>
                            <button class="diary-editor-tool diary-editor-draw-tool" type="button" data-editor-draw aria-haspopup="dialog" aria-expanded="false" aria-label="Open drawing canvas" title="Draw">✎ Draw</button>
                        </div>
                        </div>
                    </div>

                    <div class="diary-editor-emoji-picker" id="diary-emoji-picker" data-editor-emoji-picker aria-label="Choose an emoji" hidden>
                        <?php foreach (array('😀', '😂', '🥰', '😭', '😴', '❤️', '⭐', '🌸', '☕', '🎓', '👍', '🎉', '🌈', '💭', '🍀') as $emoji): ?>
                            <button type="button" data-editor-emoji="<?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Insert <?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?> emoji"><?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>

                    <p class="diary-editor-image-status" data-editor-image-status role="status" aria-live="polite" hidden></p>

                    <div class="diary-editor-image-controls" data-editor-image-controls aria-label="Selected image controls" hidden>
                        <div class="diary-editor-image-options-row">
                            <span class="diary-editor-image-controls-label">Image</span>
                            <div class="diary-editor-image-control-group" role="group" aria-label="Image size">
                                <span>Size</span>
                                <button type="button" data-editor-image-size="small" aria-pressed="false">Small</button>
                                <button type="button" data-editor-image-size="medium" aria-pressed="false">Medium</button>
                                <button type="button" data-editor-image-size="large" aria-pressed="false">Large</button>
                            </div>
                            <div class="diary-editor-image-control-group" role="group" aria-label="Image alignment">
                                <span>Align</span>
                                <button type="button" data-editor-image-align="left" aria-pressed="false">Left</button>
                                <button type="button" data-editor-image-align="center" aria-pressed="false">Center</button>
                                <button type="button" data-editor-image-align="right" aria-pressed="false">Right</button>
                            </div>
                            <div class="diary-editor-image-control-group" role="group" aria-label="Image text wrapping">
                                <span>Text Wrap</span>
                                <button type="button" data-editor-image-wrap="none" aria-pressed="false">No Wrap</button>
                                <button type="button" data-editor-image-wrap="left" aria-pressed="false">Wrap Left</button>
                                <button type="button" data-editor-image-wrap="right" aria-pressed="false">Wrap Right</button>
                            </div>
                        </div>
                        <div class="diary-editor-image-details-row">
                            <label class="diary-editor-image-text-field">
                                <span>Alt Text</span>
                                <input type="text" data-editor-image-alt maxlength="150" autocomplete="off" placeholder="Describe the image for accessibility.">
                            </label>
                            <label class="diary-editor-image-text-field">
                                <span>Caption</span>
                                <input type="text" data-editor-image-caption maxlength="250" autocomplete="off" placeholder="Add a caption (optional)">
                            </label>
                            <button class="diary-editor-image-remove" type="button" data-editor-image-remove>Remove Image</button>
                        </div>
                    </div>

                    <div class="diary-image-resize-overlay" data-editor-image-resize-overlay aria-hidden="true" hidden>
                        <span class="diary-image-resize-handle is-top-left" data-editor-image-resize="top-left"></span>
                        <span class="diary-image-resize-handle is-top-right" data-editor-image-resize="top-right"></span>
                        <span class="diary-image-resize-handle is-bottom-left" data-editor-image-resize="bottom-left"></span>
                        <span class="diary-image-resize-handle is-bottom-right" data-editor-image-resize="bottom-right"></span>
                    </div>

                    <div
                        class="diary-editor-surface"
                        id="diary-content-editor"
                        contenteditable="true"
                        role="textbox"
                        aria-labelledby="diary-content-label"
                        aria-multiline="true"
                        aria-required="true"
                        data-placeholder="Write your journal entry here..."
                        spellcheck="true"
                    ></div>

                    <textarea id="content" name="content" hidden><?php echo diaryFormValue($old, 'content'); ?></textarea>
                </div>
            </div>

            <div class="diary-form-group diary-mood-section">
            <fieldset class="diary-form-group diary-mood-fieldset">
                <legend id="diary-mood-label">Mood</legend>
                <p class="diary-mood-help" id="diary-mood-help">Choose the feeling that best matches this entry.</p>
                <input
                    type="hidden"
                    id="mood"
                    name="mood"
                    value="<?php echo htmlspecialchars($selected_mood, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <div
                    class="diary-mood-picker"
                    role="radiogroup"
                    aria-labelledby="diary-mood-label"
                    aria-describedby="diary-mood-help diary-mood-error"
                    aria-required="true"
                    data-diary-mood-picker
                >
                    <?php foreach ($mood_options as $mood => $emoji): ?>
                        <?php $is_selected = $selected_mood === $mood; ?>
                        <button
                            class="diary-mood-option<?php echo $is_selected ? ' is-selected' : ''; ?>"
                            type="button"
                            role="radio"
                            aria-checked="<?php echo $is_selected ? 'true' : 'false'; ?>"
                            data-mood="<?php echo htmlspecialchars($mood, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <span class="diary-mood-option-emoji" aria-hidden="true"><?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="diary-mood-option-label"><?php echo htmlspecialchars($mood, ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="diary-mood-client-error" id="diary-mood-error" role="alert" hidden>Please choose a mood.</p>
            </fieldset>
            </div>

            <div class="diary-form-group diary-weather-section">
                <fieldset class="diary-form-group diary-mood-fieldset diary-weather-fieldset">
                    <legend id="diary-weather-label">Weather <span class="diary-optional-label">(optional)</span></legend>
                    <p class="diary-mood-help" id="diary-weather-help">Choose the weather that matches this journal entry.</p>
                    <div class="diary-mood-picker diary-weather-picker" role="radiogroup" aria-labelledby="diary-weather-label" aria-describedby="diary-weather-help">
                        <?php foreach ($weather_options as $weather_value => $weather_option): ?>
                            <?php $weather_is_selected = $selected_weather === $weather_value; ?>
                            <label class="diary-weather-choice">
                                <input
                                    class="diary-visually-hidden"
                                    type="radio"
                                    name="weather"
                                    value="<?php echo htmlspecialchars($weather_value, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $weather_is_selected ? 'checked' : ''; ?>
                                >
                                <span class="diary-mood-option diary-weather-option">
                                    <span class="diary-mood-option-emoji" aria-hidden="true"><?php echo htmlspecialchars($weather_option['emoji'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="diary-mood-option-label"><?php echo htmlspecialchars($weather_option['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>

            <div class="diary-form-group">
                <label for="entry_date">Entry Date</label>
                <input
                    type="date"
                    id="entry_date"
                    name="entry_date"
                    required
                    value="<?php echo diaryFormValue($old, 'entry_date', date('Y-m-d')); ?>"
                >
            </div>

            <div class="diary-form-actions">
                <button type="submit">Save</button>
                <a href="index.php">Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>
