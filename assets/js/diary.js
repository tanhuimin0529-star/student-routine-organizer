(function () {
    'use strict';

    var readingPage = document.querySelector('.diary-reading-page');
    var entryLinks = document.querySelectorAll('a.diary-entry-sequence-link');
    var animationDuration = 440;
    var navigationPending = false;

    if (!readingPage || entryLinks.length === 0) {
        return;
    }

    function prefersReducedMotion() {
        return window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function resetPageTurn() {
        readingPage.classList.remove('is-turning-next', 'is-turning-previous');
        navigationPending = false;
    }

    entryLinks.forEach(function (entryLink) {
        entryLink.addEventListener('click', function (event) {
            var isModifiedClick = event.button !== 0
                || event.metaKey
                || event.ctrlKey
                || event.shiftKey
                || event.altKey;

            if (isModifiedClick || prefersReducedMotion()) {
                return;
            }

            if (navigationPending) {
                event.preventDefault();
                return;
            }

            var targetUrl = entryLink.href;

            if (!targetUrl) {
                return;
            }

            event.preventDefault();
            navigationPending = true;

            var animationClass = entryLink.classList.contains('diary-entry-sequence-next')
                ? 'is-turning-next'
                : 'is-turning-previous';

            readingPage.classList.add(animationClass);

            window.setTimeout(function () {
                window.location.href = targetUrl;
            }, animationDuration);
        });
    });

    window.addEventListener('pageshow', resetPageTurn);
}());

(function () {
    'use strict';

    var richEditors = document.querySelectorAll('[data-diary-rich-editor]');

    richEditors.forEach(function (richEditor) {
        var form = richEditor.closest('form');
        var editor = richEditor.querySelector('.diary-editor-surface');
        var contentField = form ? form.querySelector('[name="content"]') : null;
        var commandButtons = richEditor.querySelectorAll('[data-editor-command]');
        var fontFamilySelect = richEditor.querySelector('[data-editor-font-family]');
        var fontSizeSelect = richEditor.querySelector('[data-editor-font-size]');
        var colorButtons = richEditor.querySelectorAll('[data-editor-color]');
        var highlightButtons = richEditor.querySelectorAll('[data-editor-highlight]');
        var imageButton = richEditor.querySelector('[data-editor-image]');
        var imageInput = richEditor.querySelector('[data-editor-image-input]');
        var emojiButton = richEditor.querySelector('[data-editor-emoji-toggle]');
        var emojiPicker = richEditor.querySelector('[data-editor-emoji-picker]');
        var emojiOptions = richEditor.querySelectorAll('[data-editor-emoji]');
        var drawButton = richEditor.querySelector('[data-editor-draw]');
        var imageStatus = richEditor.querySelector('[data-editor-image-status]');
        var imageControls = richEditor.querySelector('[data-editor-image-controls]');
        var imageSizeButtons = richEditor.querySelectorAll('[data-editor-image-size]');
        var imageAlignButtons = richEditor.querySelectorAll('[data-editor-image-align]');
        var imageWrapButtons = richEditor.querySelectorAll('[data-editor-image-wrap]');
        var imageAltInput = richEditor.querySelector('[data-editor-image-alt]');
        var imageCaptionInput = richEditor.querySelector('[data-editor-image-caption]');
        var imageRemoveButton = richEditor.querySelector('[data-editor-image-remove]');
        var imageResizeOverlay = richEditor.querySelector('[data-editor-image-resize-overlay]');
        var imageResizeHandles = richEditor.querySelectorAll('[data-editor-image-resize]');
        var drawingControls = null;
        var drawingRotationInput = null;
        var drawingRotationValue = null;
        var drawingRemoveButton = null;
        var selectedImage = null;
        var draggedImage = null;
        var draggedMedia = null;
        var imageDropIndicator = null;
        var imageResizeState = null;
        var drawingMoveState = null;
        var drawingAlphaMasks = new WeakMap();
        var ignoredDrawingClick = null;
        var ignoredDrawingClickUntil = 0;
        var savedRange = null;
        var drawingInsertionRange = null;

        if (!form || !editor || !contentField) {
            return;
        }

        drawingControls = document.createElement('div');
        drawingControls.className = 'diary-editor-drawing-controls';
        drawingControls.setAttribute('data-editor-drawing-controls', '');
        drawingControls.setAttribute('aria-label', 'Selected drawing controls');
        drawingControls.hidden = true;
        drawingControls.innerHTML = [
            '<span class="diary-editor-drawing-controls-label">Drawing · drag to move</span>',
            '<label class="diary-editor-drawing-rotation">',
            '<span>Rotate</span>',
            '<input type="range" min="-180" max="180" step="1" value="0" data-editor-drawing-rotation aria-label="Drawing rotation">',
            '<output data-editor-drawing-rotation-value>0°</output>',
            '</label>',
            '<span class="diary-editor-drawing-resize-hint">Drag a corner handle to resize</span>',
            '<button class="diary-editor-image-remove" type="button" data-editor-drawing-remove>Remove Drawing</button>'
        ].join('');
        richEditor.insertBefore(drawingControls, imageResizeOverlay || editor);
        drawingRotationInput = drawingControls.querySelector('[data-editor-drawing-rotation]');
        drawingRotationValue = drawingControls.querySelector('[data-editor-drawing-rotation-value]');
        drawingRemoveButton = drawingControls.querySelector('[data-editor-drawing-remove]');

        var allowedEditorTags = {
            P: true,
            BR: true,
            STRONG: true,
            B: true,
            EM: true,
            I: true,
            U: true,
            INS: true,
            S: true,
            STRIKE: true,
            SPAN: true,
            UL: true,
            OL: true,
            LI: true,
            BLOCKQUOTE: true,
            DIV: true,
            FONT: true,
            IMG: true,
            FIGURE: true,
            FIGCAPTION: true
        };
        var removedEditorTags = {
            SCRIPT: true,
            STYLE: true,
            IFRAME: true,
            OBJECT: true,
            EMBED: true,
            SVG: true,
            MATH: true,
            TEMPLATE: true,
            LINK: true,
            META: true,
            SOURCE: true
        };
        var allowedFontFamilies = {
            arial: 'Arial',
            georgia: 'Georgia',
            'times new roman': 'Times New Roman',
            verdana: 'Verdana',
            'courier new': 'Courier New'
        };
        var allowedFontSizes = {
            '12px': '12px',
            '14px': '14px',
            '16px': '16px',
            '18px': '18px',
            '20px': '20px',
            '24px': '24px',
            '28px': '28px',
            '32px': '32px'
        };
        var allowedTextColors = {
            'rgb(52,46,40)': '#342e28',
            'rgb(102,118,83)': '#667653',
            'rgb(130,105,79)': '#82694f',
            'rgb(164,71,67)': '#a44743',
            'rgb(49,90,125)': '#315a7d'
        };
        var allowedHighlightColors = {
            'rgb(255,241,168)': '#fff1a8',
            'rgb(220,233,206)': '#dce9ce',
            'rgb(246,211,189)': '#f6d3bd',
            'rgb(216,231,243)': '#d8e7f3'
        };
        var allowedImageSizes = {
            small: true,
            medium: true,
            large: true
        };
        var allowedImageAlignments = {
            left: true,
            center: true,
            right: true
        };
        var allowedImageWrapModes = {
            none: true,
            left: true,
            right: true
        };
        var statefulEditorCommands = {
            bold: true,
            italic: true,
            underline: true,
            strikethrough: true,
            justifyleft: true,
            justifycenter: true,
            justifyright: true,
            justifyfull: true,
            insertunorderedlist: true,
            insertorderedlist: true
        };

        function normalizeColor(value, allowedColors) {
            var colorProbe = document.createElement('span');
            colorProbe.style.color = value || '';

            var normalizedColor = colorProbe.style.color
                .toLowerCase()
                .replace(/\s+/g, '');

            return allowedColors[normalizedColor] || '';
        }

        function normalizeFontFamily(value) {
            var normalizedFamily = (value || '')
                .split(',')[0]
                .replace(/["']/g, '')
                .trim()
                .toLowerCase();

            return allowedFontFamilies[normalizedFamily] || '';
        }

        function diaryApplicationBasePath() {
            var marker = '/modules/diary/';
            var markerPosition = window.location.pathname.indexOf(marker);

            return markerPosition === -1
                ? '/student-routine-organizer'
                : window.location.pathname.slice(0, markerPosition);
        }

        function escapeRegularExpression(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function normalizeDiaryImageSrc(value) {
            var parsedUrl;

            try {
                parsedUrl = new URL(value || '', window.location.origin);
            } catch (error) {
                return '';
            }

            if (
                parsedUrl.origin !== window.location.origin
                || parsedUrl.search !== ''
                || parsedUrl.hash !== ''
            ) {
                return '';
            }

            var uploadPrefix = diaryApplicationBasePath() + '/uploads/diary/';
            var safePathPattern = new RegExp(
                '^' + escapeRegularExpression(uploadPrefix)
                + 'user_[1-9][0-9]*/[a-f0-9]{32}\\.(?:jpe?g|png|webp)$'
            );

            return safePathPattern.test(parsedUrl.pathname) ? parsedUrl.pathname : '';
        }

        function normalizeImageAlt(value) {
            var alt = (value || 'Journal image')
                .replace(/[\u0000-\u001F\u007F]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            return (alt || 'Journal image').slice(0, 150);
        }

        function normalizeImageCaption(value) {
            return (value || '')
                .replace(/[\u0000-\u001F\u007F]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 250);
        }

        function normalizeImageSize(value) {
            var size = (value || '').trim().toLowerCase();
            return allowedImageSizes[size] ? size : '';
        }

        function normalizeImageAlignment(value) {
            var alignment = (value || '').trim().toLowerCase();
            return allowedImageAlignments[alignment] ? alignment : '';
        }

        function normalizeImageWrap(value) {
            var wrapMode = (value || '').trim().toLowerCase();
            return allowedImageWrapModes[wrapMode] ? wrapMode : '';
        }

        function normalizeImageWidth(value) {
            var normalizedWidth = (value || '').trim();

            if (!/^(?:100(?:\.0)?|[1-9][0-9]?(?:\.[0-9])?)%$/.test(normalizedWidth)) {
                return '';
            }

            var numericWidth = Number.parseFloat(normalizedWidth.slice(0, -1));
            if (!Number.isFinite(numericWidth) || numericWidth < 10 || numericWidth > 100) {
                return '';
            }

            return String(Math.round(numericWidth * 10) / 10) + '%';
        }

        function normalizeDrawingNumber(value, minimum, maximum) {
            var normalized = String(value === null || value === undefined ? '' : value).trim();

            if (!/^-?(?:0|[1-9][0-9]{0,2})(?:\.[0-9])?$/.test(normalized)) {
                return null;
            }

            var numericValue = Number.parseFloat(normalized);
            if (!Number.isFinite(numericValue) || numericValue < minimum || numericValue > maximum) {
                return null;
            }

            return Math.round(numericValue * 10) / 10;
        }

        function normalizeDrawingCoordinate(value) {
            return normalizeDrawingNumber(value, 0, 100);
        }

        function normalizeDrawingWidth(value) {
            return normalizeDrawingNumber(value, 10, 100);
        }

        function normalizeDrawingRotation(value) {
            return normalizeDrawingNumber(value, -180, 180);
        }

        function normalizeDiaryDrawingSrc(value) {
            var safeSrc = normalizeDiaryImageSrc(value);
            return safeSrc && /\.png$/.test(safeSrc) ? safeSrc : '';
        }

        function isDiaryDrawingFigure(element) {
            return Boolean(
                element
                && element.tagName === 'FIGURE'
                && element.getAttribute('data-diary-object') === 'drawing'
            );
        }

        function drawingFigureForImage(image) {
            var figure = imageFigure(image);
            return isDiaryDrawingFigure(figure) ? figure : null;
        }

        function drawingMetadata(figure) {
            if (!isDiaryDrawingFigure(figure)) {
                return null;
            }

            var x = normalizeDrawingCoordinate(figure.getAttribute('data-diary-x'));
            var y = normalizeDrawingCoordinate(figure.getAttribute('data-diary-y'));
            var width = normalizeDrawingWidth(figure.getAttribute('data-diary-width'));
            var rotation = normalizeDrawingRotation(figure.getAttribute('data-diary-rotation'));

            return x === null || y === null || width === null || rotation === null
                ? null
                : { x: x, y: y, width: width, rotation: rotation };
        }

        function setDrawingMetadata(figure, metadata) {
            figure.setAttribute('data-diary-object', 'drawing');
            figure.setAttribute('data-diary-x', String(Math.round(metadata.x * 10) / 10));
            figure.setAttribute('data-diary-y', String(Math.round(metadata.y * 10) / 10));
            figure.setAttribute('data-diary-width', String(Math.round(metadata.width * 10) / 10));
            figure.setAttribute('data-diary-rotation', String(Math.round(metadata.rotation * 10) / 10));
        }

        function applyDrawingPresentation(figure) {
            var metadata = drawingMetadata(figure);
            if (!metadata) {
                return;
            }

            figure.style.left = metadata.x + '%';
            figure.style.top = metadata.y + '%';
            figure.style.width = metadata.width + '%';
            figure.style.transform = 'translate(-50%, -50%) rotate(' + metadata.rotation + 'deg)';
        }

        function drawingAspectRatio(figure) {
            var image = figure ? figure.querySelector('img') : null;
            if (!image) {
                return 9 / 5;
            }

            if (image.naturalWidth > 0 && image.naturalHeight > 0) {
                return image.naturalWidth / image.naturalHeight;
            }

            var rectangle = image.getBoundingClientRect();
            return rectangle.width > 0 && rectangle.height > 0
                ? rectangle.width / rectangle.height
                : 9 / 5;
        }

        function clampDrawingToEditor(figure) {
            var metadata = drawingMetadata(figure);
            if (!metadata || editor.clientWidth < 1 || editor.clientHeight < 1) {
                return metadata;
            }

            var aspectRatio = Math.max(0.1, drawingAspectRatio(figure));
            var radians = Math.abs(metadata.rotation) * Math.PI / 180;
            var cosine = Math.abs(Math.cos(radians));
            var sine = Math.abs(Math.sin(radians));
            var widthFactor = cosine + (sine / aspectRatio);
            var heightFactor = sine + (cosine / aspectRatio);
            var maximumByWidth = 100 / Math.max(widthFactor, 0.01);
            var maximumByHeight = (editor.clientHeight / editor.clientWidth)
                * 100 / Math.max(heightFactor, 0.01);
            var maximumWidth = Math.max(10, Math.min(100, maximumByWidth, maximumByHeight));

            metadata.width = Math.min(metadata.width, maximumWidth);

            var drawingWidth = editor.clientWidth * metadata.width / 100;
            var drawingHeight = drawingWidth / aspectRatio;
            var halfBoundsWidth = ((drawingWidth * cosine) + (drawingHeight * sine)) / 2;
            var halfBoundsHeight = ((drawingWidth * sine) + (drawingHeight * cosine)) / 2;
            var minimumX = Math.min(50, halfBoundsWidth / editor.clientWidth * 100);
            var minimumY = Math.min(50, halfBoundsHeight / editor.clientHeight * 100);

            metadata.x = Math.min(100 - minimumX, Math.max(minimumX, metadata.x));
            metadata.y = Math.min(100 - minimumY, Math.max(minimumY, metadata.y));
            setDrawingMetadata(figure, metadata);
            applyDrawingPresentation(figure);

            return metadata;
        }

        function drawingAlphaMask(image) {
            if (!image || !image.complete || image.naturalWidth < 1 || image.naturalHeight < 1) {
                return null;
            }

            var cachedMask = drawingAlphaMasks.get(image);
            if (cachedMask) {
                return cachedMask;
            }

            var maskCanvas = document.createElement('canvas');
            maskCanvas.width = image.naturalWidth;
            maskCanvas.height = image.naturalHeight;
            var maskContext = maskCanvas.getContext('2d', { willReadFrequently: true });
            if (!maskContext) {
                return null;
            }

            try {
                maskContext.clearRect(0, 0, maskCanvas.width, maskCanvas.height);
                maskContext.drawImage(image, 0, 0);
            } catch (error) {
                return null;
            }

            cachedMask = {
                context: maskContext,
                width: maskCanvas.width,
                height: maskCanvas.height
            };
            drawingAlphaMasks.set(image, cachedMask);
            return cachedMask;
        }

        function drawingHasVisibleInkAtPoint(figure, clientX, clientY) {
            var metadata = drawingMetadata(figure);
            var image = figure ? figure.querySelector('img') : null;
            var mask = drawingAlphaMask(image);

            // Until the local PNG has loaded, keep the object selectable rather
            // than incorrectly passing a genuine drawing click through.
            if (!metadata || !image || !mask) {
                return true;
            }

            var rectangle = figure.getBoundingClientRect();
            var figureWidth = figure.offsetWidth;
            var figureHeight = figure.offsetHeight;
            if (figureWidth < 1 || figureHeight < 1) {
                return false;
            }

            var centerX = rectangle.left + (rectangle.width / 2);
            var centerY = rectangle.top + (rectangle.height / 2);
            var radians = metadata.rotation * Math.PI / 180;
            var cosine = Math.cos(radians);
            var sine = Math.sin(radians);
            var relativeX = clientX - centerX;
            var relativeY = clientY - centerY;
            var localX = (relativeX * cosine) + (relativeY * sine) + (figureWidth / 2);
            var localY = (-relativeX * sine) + (relativeY * cosine) + (figureHeight / 2);

            if (localX < 0 || localY < 0 || localX >= figureWidth || localY >= figureHeight) {
                return false;
            }

            var sourceX = localX / figureWidth * mask.width;
            var sourceY = localY / figureHeight * mask.height;
            var radiusX = Math.max(1, Math.ceil(3 * mask.width / figureWidth));
            var radiusY = Math.max(1, Math.ceil(3 * mask.height / figureHeight));
            var sampleLeft = Math.max(0, Math.floor(sourceX - radiusX));
            var sampleTop = Math.max(0, Math.floor(sourceY - radiusY));
            var sampleRight = Math.min(mask.width, Math.ceil(sourceX + radiusX + 1));
            var sampleBottom = Math.min(mask.height, Math.ceil(sourceY + radiusY + 1));

            try {
                var pixels = mask.context.getImageData(
                    sampleLeft,
                    sampleTop,
                    Math.max(1, sampleRight - sampleLeft),
                    Math.max(1, sampleBottom - sampleTop)
                ).data;

                for (var pixelIndex = 3; pixelIndex < pixels.length; pixelIndex += 4) {
                    if (pixels[pixelIndex] > 12) {
                        return true;
                    }
                }
            } catch (error) {
                return true;
            }

            return false;
        }

        function caretRangeAtPoint(clientX, clientY) {
            if (document.caretRangeFromPoint) {
                return document.caretRangeFromPoint(clientX, clientY);
            }

            if (document.caretPositionFromPoint) {
                var caretPosition = document.caretPositionFromPoint(clientX, clientY);
                if (caretPosition) {
                    var caretRange = document.createRange();
                    caretRange.setStart(caretPosition.offsetNode, caretPosition.offset);
                    caretRange.collapse(true);
                    return caretRange;
                }
            }

            return null;
        }

        function focusEditorAtRange(preferredRange) {
            var targetRange = preferredRange;
            if (!targetRange || !editor.contains(targetRange.commonAncestorContainer)) {
                targetRange = document.createRange();
                targetRange.selectNodeContents(editor);
                targetRange.collapse(false);
            } else {
                targetRange = targetRange.cloneRange();
                targetRange.collapse(true);
            }

            clearDiaryImageSelection();
            editor.focus();
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(targetRange);
            savedRange = targetRange.cloneRange();
            updateToolbarState();
        }

        function passDrawingClickThroughToEditor(event, figure) {
            ignoredDrawingClick = figure;
            ignoredDrawingClickUntil = Date.now() + 600;
            figure.style.pointerEvents = 'none';
            var targetRange = caretRangeAtPoint(event.clientX, event.clientY);
            figure.style.removeProperty('pointer-events');

            if (!targetRange || !editor.contains(targetRange.commonAncestorContainer)) {
                targetRange = null;
            }

            focusEditorAtRange(targetRange);
        }

        function imageFigure(image) {
            return image && image.parentElement && image.parentElement.tagName === 'FIGURE'
                ? image.parentElement
                : null;
        }

        function imageLayoutElement(image) {
            return imageFigure(image) || image;
        }

        function removeImageLayoutAttributes(element) {
            if (!element) {
                return;
            }

            element.removeAttribute('data-diary-size');
            element.removeAttribute('data-diary-align');
            element.removeAttribute('data-diary-wrap');
            element.style.removeProperty('width');
            if (!element.getAttribute('style')) {
                element.removeAttribute('style');
            }
        }

        function copySafeImageLayout(sourceElement, targetElement, fallbackElement) {
            var size = normalizeImageSize(sourceElement.getAttribute('data-diary-size'))
                || (fallbackElement
                    ? normalizeImageSize(fallbackElement.getAttribute('data-diary-size'))
                    : '');
            var alignment = normalizeImageAlignment(sourceElement.getAttribute('data-diary-align'))
                || (fallbackElement
                    ? normalizeImageAlignment(fallbackElement.getAttribute('data-diary-align'))
                    : '');
            var wrapMode = normalizeImageWrap(sourceElement.getAttribute('data-diary-wrap'))
                || (fallbackElement
                    ? normalizeImageWrap(fallbackElement.getAttribute('data-diary-wrap'))
                    : '');
            var width = normalizeImageWidth(sourceElement.style.width)
                || (fallbackElement ? normalizeImageWidth(fallbackElement.style.width) : '');

            if (width) {
                targetElement.style.width = width;
            } else if (size) {
                targetElement.setAttribute('data-diary-size', size);
            } else {
                targetElement.setAttribute('data-diary-size', 'medium');
            }

            targetElement.setAttribute('data-diary-align', alignment || 'center');
            targetElement.setAttribute('data-diary-wrap', wrapMode || 'none');
        }

        function ensureImageFigure(image) {
            var existingFigure = imageFigure(image);
            if (existingFigure) {
                return existingFigure;
            }

            var figure = document.createElement('figure');
            copySafeImageLayout(image, figure, null);
            image.parentNode.insertBefore(figure, image);
            figure.appendChild(image);
            removeImageLayoutAttributes(image);
            return figure;
        }

        function imageCaptionElement(image) {
            var figure = imageFigure(image);
            if (!figure) {
                return null;
            }

            return Array.prototype.find.call(figure.children, function (child) {
                return child.tagName === 'FIGCAPTION';
            }) || null;
        }

        function applySafeEditorStyles(sourceElement, cleanElement) {
            var fontFamily = normalizeFontFamily(
                sourceElement.style.fontFamily || sourceElement.getAttribute('face')
            );
            var fontSize = sourceElement.style.fontSize;
            var textColor = normalizeColor(
                sourceElement.style.color || sourceElement.getAttribute('color'),
                allowedTextColors
            );
            var highlightColor = normalizeColor(
                sourceElement.style.backgroundColor,
                allowedHighlightColors
            );
            var textAlign = sourceElement.style.textAlign || sourceElement.getAttribute('align');

            if (fontFamily) {
                cleanElement.style.fontFamily = fontFamily;
            }

            if (allowedFontSizes[fontSize]) {
                cleanElement.style.fontSize = allowedFontSizes[fontSize];
            }

            if (textColor) {
                cleanElement.style.color = textColor;
            }

            if (highlightColor) {
                cleanElement.style.backgroundColor = highlightColor;
            }

            if (['left', 'center', 'right', 'justify'].indexOf(textAlign) !== -1) {
                cleanElement.style.textAlign = textAlign;
            }
        }

        function elementUsesUnderlineStyle(element) {
            var decoration = [
                element.style.textDecorationLine,
                element.style.textDecoration
            ].join(' ').toLowerCase();

            return decoration.split(/\s+/).indexOf('underline') !== -1;
        }

        function sanitizeEditorNode(sourceNode) {
            if (sourceNode.nodeType === Node.TEXT_NODE) {
                return document.createTextNode(sourceNode.nodeValue || '');
            }

            if (sourceNode.nodeType !== Node.ELEMENT_NODE) {
                return document.createDocumentFragment();
            }

            var sourceTag = sourceNode.tagName.toUpperCase();

            if (removedEditorTags[sourceTag]) {
                return document.createDocumentFragment();
            }

            if (!allowedEditorTags[sourceTag]) {
                var unwrappedContent = document.createDocumentFragment();

                Array.prototype.forEach.call(sourceNode.childNodes, function (childNode) {
                    unwrappedContent.appendChild(sanitizeEditorNode(childNode));
                });

                return unwrappedContent;
            }

            if (sourceTag === 'FIGCAPTION') {
                return document.createDocumentFragment();
            }

            if (sourceTag === 'FIGURE') {
                var sourceFigureImage = Array.prototype.find.call(
                    sourceNode.children,
                    function (child) {
                        return child.tagName === 'IMG';
                    }
                );

                if (!sourceFigureImage) {
                    return document.createDocumentFragment();
                }

                if (sourceNode.getAttribute('data-diary-object') === 'drawing') {
                    var drawingSrc = normalizeDiaryDrawingSrc(sourceFigureImage.getAttribute('src'));
                    var drawingX = normalizeDrawingCoordinate(sourceNode.getAttribute('data-diary-x'));
                    var drawingY = normalizeDrawingCoordinate(sourceNode.getAttribute('data-diary-y'));
                    var drawingWidth = normalizeDrawingWidth(sourceNode.getAttribute('data-diary-width'));
                    var drawingRotation = normalizeDrawingRotation(sourceNode.getAttribute('data-diary-rotation'));

                    if (
                        !drawingSrc
                        || drawingX === null
                        || drawingY === null
                        || drawingWidth === null
                        || drawingRotation === null
                    ) {
                        return document.createDocumentFragment();
                    }

                    var cleanDrawing = document.createElement('figure');
                    setDrawingMetadata(cleanDrawing, {
                        x: drawingX,
                        y: drawingY,
                        width: drawingWidth,
                        rotation: drawingRotation
                    });

                    var cleanDrawingImage = document.createElement('img');
                    cleanDrawingImage.src = drawingSrc;
                    cleanDrawingImage.alt = normalizeImageAlt(sourceFigureImage.getAttribute('alt'));
                    cleanDrawing.appendChild(cleanDrawingImage);

                    return cleanDrawing;
                }

                var cleanFigureImage = sanitizeEditorNode(sourceFigureImage);
                if (!cleanFigureImage || cleanFigureImage.tagName !== 'IMG') {
                    return document.createDocumentFragment();
                }

                var cleanFigure = document.createElement('figure');
                copySafeImageLayout(sourceNode, cleanFigure, sourceFigureImage);
                removeImageLayoutAttributes(cleanFigureImage);
                cleanFigure.appendChild(cleanFigureImage);

                var sourceFigureCaption = Array.prototype.find.call(
                    sourceNode.children,
                    function (child) {
                        return child.tagName === 'FIGCAPTION';
                    }
                );
                var cleanCaptionText = sourceFigureCaption
                    ? normalizeImageCaption(sourceFigureCaption.textContent)
                    : '';

                if (cleanCaptionText) {
                    var cleanFigureCaption = document.createElement('figcaption');
                    cleanFigureCaption.textContent = cleanCaptionText;
                    cleanFigureCaption.setAttribute('contenteditable', 'false');
                    cleanFigure.appendChild(cleanFigureCaption);
                }

                return cleanFigure;
            }

            if (sourceTag === 'IMG') {
                var safeImageSrc = normalizeDiaryImageSrc(sourceNode.getAttribute('src'));

                if (!safeImageSrc) {
                    return document.createDocumentFragment();
                }

                var cleanImage = document.createElement('img');
                cleanImage.src = safeImageSrc;
                cleanImage.alt = normalizeImageAlt(sourceNode.getAttribute('alt'));

                var imageSize = normalizeImageSize(
                    sourceNode.getAttribute('data-diary-size')
                );
                var imageAlignment = normalizeImageAlignment(
                    sourceNode.getAttribute('data-diary-align')
                );
                var imageWrapMode = normalizeImageWrap(
                    sourceNode.getAttribute('data-diary-wrap')
                );

                if (imageSize) {
                    cleanImage.setAttribute('data-diary-size', imageSize);
                }
                if (imageAlignment) {
                    cleanImage.setAttribute('data-diary-align', imageAlignment);
                }
                if (imageWrapMode) {
                    cleanImage.setAttribute('data-diary-wrap', imageWrapMode);
                }

                var imageWidth = normalizeImageWidth(sourceNode.style.width);
                if (imageWidth) {
                    cleanImage.style.width = imageWidth;
                }

                return cleanImage;
            }

            var cleanTag = sourceTag === 'FONT'
                ? 'span'
                : (sourceTag === 'STRIKE'
                    ? 's'
                    : (sourceTag === 'INS' ? 'u' : sourceTag.toLowerCase()));
            var cleanElement = document.createElement(cleanTag);

            applySafeEditorStyles(sourceNode, cleanElement);

            Array.prototype.forEach.call(sourceNode.childNodes, function (childNode) {
                cleanElement.appendChild(sanitizeEditorNode(childNode));
            });

            if (cleanTag === 'span' && elementUsesUnderlineStyle(sourceNode)) {
                var cleanUnderline = document.createElement('u');

                if (cleanElement.hasAttributes()) {
                    cleanUnderline.appendChild(cleanElement);
                } else {
                    while (cleanElement.firstChild) {
                        cleanUnderline.appendChild(cleanElement.firstChild);
                    }
                }

                return cleanUnderline;
            }

            return cleanElement;
        }

        function contentLooksFormatted(content) {
            return /<\/?(?:p|br|strong|b|em|i|u|s|strike|span|ul|ol|li|blockquote|div|font|img|figure|figcaption)\b/i.test(content);
        }

        function loadInitialContent(content) {
            if (!content) {
                return;
            }

            if (!contentLooksFormatted(content)) {
                var textLines = content.split(/\r\n|\r|\n/);

                textLines.forEach(function (textLine, lineIndex) {
                    if (lineIndex > 0) {
                        editor.appendChild(document.createElement('br'));
                    }

                    editor.appendChild(document.createTextNode(textLine));
                });

                return;
            }

            var parsedDocument = new DOMParser().parseFromString(content, 'text/html');
            var safeContent = document.createDocumentFragment();

            Array.prototype.forEach.call(parsedDocument.body.childNodes, function (childNode) {
                safeContent.appendChild(sanitizeEditorNode(childNode));
            });

            editor.replaceChildren(safeContent);
        }

        function selectionIsInsideEditor() {
            var selection = window.getSelection();

            return selection
                && selection.rangeCount > 0
                && editor.contains(selection.getRangeAt(0).commonAncestorContainer);
        }

        function rememberSelection() {
            var selection = window.getSelection();

            if (selectionIsInsideEditor()) {
                savedRange = selection.getRangeAt(0).cloneRange();
            }
        }

        function selectionStyleNodes(range) {
            var styleNodes = [];

            if (range.collapsed) {
                var cursorNode = range.startContainer;
                if (cursorNode.nodeType !== Node.ELEMENT_NODE) {
                    cursorNode = cursorNode.parentElement;
                }
                return cursorNode ? [cursorNode] : [];
            }

            var walker = document.createTreeWalker(
                editor,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode: function (textNode) {
                        if (!textNode.nodeValue || textNode.nodeValue.trim() === '') {
                            return NodeFilter.FILTER_REJECT;
                        }

                        try {
                            return range.intersectsNode(textNode)
                                ? NodeFilter.FILTER_ACCEPT
                                : NodeFilter.FILTER_REJECT;
                        } catch (error) {
                            return NodeFilter.FILTER_REJECT;
                        }
                    }
                }
            );
            var currentNode;

            while ((currentNode = walker.nextNode())) {
                if (currentNode.parentElement) {
                    styleNodes.push(currentNode.parentElement);
                }
            }

            if (styleNodes.length === 0) {
                var commonNode = range.commonAncestorContainer;
                if (commonNode.nodeType !== Node.ELEMENT_NODE) {
                    commonNode = commonNode.parentElement;
                }
                if (commonNode) {
                    styleNodes.push(commonNode);
                }
            }

            return styleNodes;
        }

        function selectedStyleState(range, propertyName, normalizer) {
            var values = [];

            selectionStyleNodes(range).forEach(function (styleNode) {
                var normalizedValue = normalizer(
                    window.getComputedStyle(styleNode)[propertyName]
                );

                if (normalizedValue && values.indexOf(normalizedValue) === -1) {
                    values.push(normalizedValue);
                }
            });

            return {
                value: values.length === 1 ? values[0] : '',
                mixed: values.length > 1
            };
        }

        function updateSelectState(selectElement, state, placeholder) {
            if (!selectElement || selectElement.options.length === 0) {
                return;
            }

            selectElement.options[0].textContent = state.mixed ? 'Mixed' : placeholder;
            selectElement.value = state.value || '';
        }

        function editorCommandIsActive(commandName) {
            try {
                return document.queryCommandState(commandName);
            } catch (error) {
                return false;
            }
        }

        function updateToolbarState() {
            var selection = window.getSelection();
            if (!selectionIsInsideEditor() || !selection || selection.rangeCount === 0) {
                return;
            }

            var range = selection.getRangeAt(0);
            var fontState = selectedStyleState(range, 'fontFamily', normalizeFontFamily);
            var sizeState = selectedStyleState(range, 'fontSize', function (fontSize) {
                return allowedFontSizes[fontSize] ? fontSize.slice(0, -2) : '';
            });

            updateSelectState(fontFamilySelect, fontState, 'Font');
            updateSelectState(fontSizeSelect, sizeState, 'Size');

            commandButtons.forEach(function (button) {
                var commandName = (button.getAttribute('data-editor-command') || '').toLowerCase();
                if (!statefulEditorCommands[commandName]) {
                    return;
                }

                var isActive = editorCommandIsActive(commandName);
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function scheduleToolbarStateUpdate() {
            window.requestAnimationFrame(updateToolbarState);
        }

        function restoreSelection() {
            if (!savedRange) {
                editor.focus();
                return;
            }

            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(savedRange);
        }

        function closeEmojiPicker() {
            if (!emojiPicker || !emojiButton) {
                return;
            }

            emojiPicker.hidden = true;
            emojiButton.setAttribute('aria-expanded', 'false');
        }

        function insertEmojiAtCursor(emoji) {
            restoreSelection();

            var selection = window.getSelection();
            var range;

            if (!selection || selection.rangeCount === 0 || !selectionIsInsideEditor()) {
                editor.focus();
                range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
                selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                range = selection.getRangeAt(0);
            }

            range.deleteContents();
            var emojiNode = document.createTextNode(emoji);
            range.insertNode(emojiNode);
            range.setStartAfter(emojiNode);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
            synchronizeContent();
            updateToolbarState();
        }

        function editorHasVisibleContent() {
            return editor.textContent.replace(/\u200B/g, '').trim() !== ''
                || editor.querySelector('img') !== null;
        }

        function synchronizeContent() {
            if (!editorHasVisibleContent()) {
                contentField.value = '';
                return;
            }

            var storageEditor = editor.cloneNode(true);
            storageEditor.querySelectorAll('.is-selected, .is-dragging, .is-resizing, .is-moving').forEach(function (element) {
                element.classList.remove('is-selected', 'is-dragging', 'is-resizing', 'is-moving');
                if (!element.getAttribute('class')) {
                    element.removeAttribute('class');
                }
            });
            storageEditor.querySelectorAll('img[draggable], img[aria-grabbed]').forEach(function (image) {
                image.removeAttribute('draggable');
                image.removeAttribute('aria-grabbed');
            });
            storageEditor.querySelectorAll('.diary-image-drop-indicator').forEach(function (indicator) {
                indicator.remove();
            });
            storageEditor.querySelectorAll('figure[data-diary-object="drawing"]').forEach(function (drawing) {
                drawing.removeAttribute('style');
                drawing.removeAttribute('class');
                drawing.removeAttribute('contenteditable');
                drawing.querySelectorAll('img').forEach(function (drawingImage) {
                    drawingImage.removeAttribute('style');
                    drawingImage.removeAttribute('class');
                    drawingImage.removeAttribute('draggable');
                    drawingImage.removeAttribute('aria-grabbed');
                });
            });
            contentField.value = storageEditor.innerHTML.trim();
        }

        function normalizeUnderlineElements() {
            editor.querySelectorAll('ins').forEach(function (insertElement) {
                var underlineElement = document.createElement('u');

                while (insertElement.firstChild) {
                    underlineElement.appendChild(insertElement.firstChild);
                }

                insertElement.replaceWith(underlineElement);
            });

            editor.querySelectorAll('span[style], font[style]').forEach(function (styledElement) {
                if (!elementUsesUnderlineStyle(styledElement)) {
                    return;
                }

                styledElement.style.removeProperty('text-decoration');
                styledElement.style.removeProperty('text-decoration-line');
                styledElement.style.removeProperty('text-decoration-style');
                styledElement.style.removeProperty('text-decoration-color');
                styledElement.style.removeProperty('text-decoration-thickness');

                var underlineElement = document.createElement('u');
                styledElement.replaceWith(underlineElement);

                if (styledElement.tagName === 'SPAN' && !styledElement.hasAttributes()) {
                    while (styledElement.firstChild) {
                        underlineElement.appendChild(styledElement.firstChild);
                    }
                } else {
                    underlineElement.appendChild(styledElement);
                }
            });
        }

        function normalizeHighlightElements() {
            editor.querySelectorAll('[style]').forEach(function (styledElement) {
                var highlightColor = normalizeColor(
                    styledElement.style.backgroundColor,
                    allowedHighlightColors
                );

                styledElement.style.removeProperty('background');
                styledElement.style.removeProperty('background-color');

                if (!highlightColor) {
                    return;
                }

                if (styledElement.tagName === 'SPAN' || styledElement.tagName === 'FONT') {
                    styledElement.style.backgroundColor = highlightColor;
                    return;
                }

                var highlightElement = document.createElement('span');
                highlightElement.style.backgroundColor = highlightColor;

                while (styledElement.firstChild) {
                    highlightElement.appendChild(styledElement.firstChild);
                }

                styledElement.appendChild(highlightElement);
            });
        }

        function normalizeFontElements(sizeOverride) {
            editor.querySelectorAll('font').forEach(function (fontElement) {
                var replacement = document.createElement('span');
                var face = fontElement.getAttribute('face');
                var color = fontElement.getAttribute('color');
                var size = fontElement.getAttribute('size');
                var highlightColor = normalizeColor(
                    fontElement.style.backgroundColor,
                    allowedHighlightColors
                );

                if (face) {
                    replacement.style.fontFamily = face;
                }

                if (color) {
                    replacement.style.color = color;
                }

                if (size && sizeOverride) {
                    replacement.style.fontSize = sizeOverride + 'px';
                }

                if (highlightColor) {
                    replacement.style.backgroundColor = highlightColor;
                }

                while (fontElement.firstChild) {
                    replacement.appendChild(fontElement.firstChild);
                }

                fontElement.replaceWith(replacement);
            });
        }

        function setImageStatus(message, isError) {
            if (!imageStatus) {
                return;
            }

            imageStatus.textContent = message || '';
            imageStatus.hidden = !message;
            imageStatus.classList.toggle('is-error', Boolean(isError));
        }

        function updateImageControlState() {
            var selectedDrawing = selectedImage ? drawingFigureForImage(selectedImage) : null;
            var layoutElement = selectedImage && !selectedDrawing
                ? imageLayoutElement(selectedImage) : null;
            var selectedWidth = layoutElement
                ? normalizeImageWidth(layoutElement.style.width)
                : '';
            var selectedSize = layoutElement && !selectedWidth
                ? normalizeImageSize(layoutElement.getAttribute('data-diary-size')) || 'medium'
                : '';
            var selectedAlignment = layoutElement
                ? normalizeImageAlignment(layoutElement.getAttribute('data-diary-align')) || 'center'
                : '';
            var selectedWrapMode = layoutElement
                ? normalizeImageWrap(layoutElement.getAttribute('data-diary-wrap')) || 'none'
                : '';

            imageSizeButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-editor-image-size') === selectedSize;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            imageAlignButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-editor-image-align') === selectedAlignment;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            imageWrapButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-editor-image-wrap') === selectedWrapMode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            if (imageAltInput && document.activeElement !== imageAltInput) {
                imageAltInput.value = selectedImage
                    ? normalizeImageAlt(selectedImage.getAttribute('alt'))
                    : '';
            }

            if (imageCaptionInput && document.activeElement !== imageCaptionInput) {
                var caption = selectedImage ? imageCaptionElement(selectedImage) : null;
                imageCaptionInput.value = caption
                    ? normalizeImageCaption(caption.textContent)
                    : '';
            }

            if (selectedDrawing && drawingRotationInput && drawingRotationValue) {
                var drawingRotation = normalizeDrawingRotation(
                    selectedDrawing.getAttribute('data-diary-rotation')
                );
                drawingRotation = drawingRotation === null ? 0 : drawingRotation;
                drawingRotationInput.value = String(drawingRotation);
                drawingRotationValue.value = drawingRotation + '°';
                drawingRotationValue.textContent = drawingRotation + '°';
            }
        }

        function positionImageResizeOverlay() {
            if (!imageResizeOverlay) {
                return;
            }

            if (
                !selectedImage
                || !editor.contains(selectedImage)
                || draggedImage === selectedImage
            ) {
                imageResizeOverlay.hidden = true;
                return;
            }

            var imageRectangle = selectedImage.getBoundingClientRect();
            var editorRectangle = richEditor.getBoundingClientRect();

            if (imageRectangle.width <= 0 || imageRectangle.height <= 0) {
                imageResizeOverlay.hidden = true;
                return;
            }

            imageResizeOverlay.style.left = (imageRectangle.left - editorRectangle.left) + 'px';
            imageResizeOverlay.style.top = (imageRectangle.top - editorRectangle.top) + 'px';
            imageResizeOverlay.style.width = imageRectangle.width + 'px';
            imageResizeOverlay.style.height = imageRectangle.height + 'px';
            imageResizeOverlay.hidden = false;
        }

        function selectDiaryImage(image) {
            if (selectedImage && selectedImage !== image) {
                selectedImage.classList.remove('is-selected');
            }

            selectedImage = image && editor.contains(image) ? image : null;

            if (selectedImage) {
                selectedImage.classList.add('is-selected');
            }

            if (imageControls) {
                imageControls.hidden = !selectedImage || Boolean(drawingFigureForImage(selectedImage));
            }

            if (drawingControls) {
                drawingControls.hidden = !selectedImage || !drawingFigureForImage(selectedImage);
            }

            updateImageControlState();
            positionImageResizeOverlay();
            synchronizeContent();
        }

        function clearDiaryImageSelection() {
            selectDiaryImage(null);
        }

        function prepareEditorImages() {
            editor.querySelectorAll('img').forEach(function (image) {
                var drawingFigure = drawingFigureForImage(image);

                if (drawingFigure && normalizeDiaryDrawingSrc(image.getAttribute('src'))) {
                    drawingFigure.setAttribute('contenteditable', 'false');
                    image.removeAttribute('draggable');
                    applyDrawingPresentation(drawingFigure);
                    image.addEventListener('load', function () {
                        clampDrawingToEditor(drawingFigure);
                        if (selectedImage === image) {
                            positionImageResizeOverlay();
                        }
                    });
                    clampDrawingToEditor(drawingFigure);
                    return;
                }

                if (normalizeDiaryImageSrc(image.getAttribute('src'))) {
                    var figure = ensureImageFigure(image);
                    var caption = imageCaptionElement(image);
                    if (caption) {
                        caption.setAttribute('contenteditable', 'false');
                    }
                    image.setAttribute('draggable', 'true');
                    image.addEventListener('load', function () {
                        if (selectedImage === image) {
                            positionImageResizeOverlay();
                        }
                    });
                } else {
                    image.removeAttribute('draggable');
                }
            });
        }

        function editorContentWidth() {
            var editorStyles = window.getComputedStyle(editor);
            var horizontalPadding = parseFloat(editorStyles.paddingLeft || 0)
                + parseFloat(editorStyles.paddingRight || 0);

            return Math.max(1, editor.clientWidth - horizontalPadding);
        }

        function beginImageResize(event, handle) {
            if (
                (event.pointerType === 'mouse' && event.button !== 0)
                ||
                !selectedImage
                || !editor.contains(selectedImage)
                || !normalizeDiaryImageSrc(selectedImage.getAttribute('src'))
            ) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            var selectedDrawing = drawingFigureForImage(selectedImage);
            var imageRectangle = (selectedDrawing || selectedImage).getBoundingClientRect();
            var maximumWidth = editorContentWidth();
            var minimumWidth = Math.min(100, maximumWidth);

            if (selectedDrawing) {
                var drawingData = drawingMetadata(selectedDrawing);
                var drawingCenterX = imageRectangle.left + (imageRectangle.width / 2);
                var drawingCenterY = imageRectangle.top + (imageRectangle.height / 2);
                imageResizeState = {
                    kind: 'drawing',
                    pointerId: event.pointerId,
                    handle: handle,
                    startWidthPercent: drawingData ? drawingData.width : 35,
                    centerX: drawingCenterX,
                    centerY: drawingCenterY,
                    startDistance: Math.max(1, Math.hypot(
                        event.clientX - drawingCenterX,
                        event.clientY - drawingCenterY
                    ))
                };
            } else {
                imageResizeState = {
                    kind: 'image',
                    pointerId: event.pointerId,
                    handle: handle,
                    corner: handle.getAttribute('data-editor-image-resize') || 'bottom-right',
                    startX: event.clientX,
                    startY: event.clientY,
                    startWidth: imageRectangle.width,
                    aspectRatio: imageRectangle.height > 0 ? imageRectangle.width / imageRectangle.height : 1,
                    minimumWidth: minimumWidth,
                    maximumWidth: maximumWidth
                };
            }

            selectedImage.classList.add('is-resizing');
            imageResizeOverlay.classList.add('is-resizing');

            if (handle.setPointerCapture) {
                handle.setPointerCapture(event.pointerId);
            }
        }

        function moveImageResize(event) {
            if (!imageResizeState || event.pointerId !== imageResizeState.pointerId) {
                return;
            }


            if (imageResizeState.kind === 'drawing') {
                var drawingFigure = drawingFigureForImage(selectedImage);
                var drawingData = drawingFigure ? drawingMetadata(drawingFigure) : null;
                if (!drawingFigure || !drawingData) {
                    return;
                }

                var pointerDistance = Math.max(1, Math.hypot(
                    event.clientX - imageResizeState.centerX,
                    event.clientY - imageResizeState.centerY
                ));
                var requestedDrawingWidth = imageResizeState.startWidthPercent
                    * pointerDistance / imageResizeState.startDistance;
                drawingData.width = Math.min(100, Math.max(10, requestedDrawingWidth));
                setDrawingMetadata(drawingFigure, drawingData);
                clampDrawingToEditor(drawingFigure);
                updateImageControlState();
                positionImageResizeOverlay();
                return;
            }
            event.preventDefault();

            var horizontalDirection = imageResizeState.corner.indexOf('left') !== -1 ? -1 : 1;
            var verticalDirection = imageResizeState.corner.indexOf('top') !== -1 ? -1 : 1;
            var horizontalChange = (event.clientX - imageResizeState.startX)
                * horizontalDirection;
            var verticalChange = (event.clientY - imageResizeState.startY)
                * verticalDirection
                * imageResizeState.aspectRatio;
            var selectedChange = Math.abs(horizontalChange) >= Math.abs(verticalChange)
                ? horizontalChange
                : verticalChange;
            var requestedWidth = imageResizeState.startWidth + selectedChange;
            var clampedWidth = Math.min(
                imageResizeState.maximumWidth,
                Math.max(imageResizeState.minimumWidth, requestedWidth)
            );
            var percentageWidth = Math.min(
                100,
                Math.max(10, (clampedWidth / imageResizeState.maximumWidth) * 100)
            );
            var safeWidth = normalizeImageWidth(
                (Math.round(percentageWidth * 10) / 10) + '%'
            );

            if (!safeWidth || !selectedImage) {
                return;
            }

            var layoutElement = imageLayoutElement(selectedImage);
            layoutElement.style.width = safeWidth;
            layoutElement.removeAttribute('data-diary-size');
            updateImageControlState();
            positionImageResizeOverlay();
        }

        function finishImageResize(event) {
            if (!imageResizeState || event.pointerId !== imageResizeState.pointerId) {
                return;
            }

            var resizeHandle = imageResizeState.handle;
            if (
                resizeHandle.releasePointerCapture
                && resizeHandle.hasPointerCapture
                && resizeHandle.hasPointerCapture(event.pointerId)
            ) {
                resizeHandle.releasePointerCapture(event.pointerId);
            }

            if (selectedImage) {
                selectedImage.classList.remove('is-resizing');
            }
            if (imageResizeOverlay) {
                imageResizeOverlay.classList.remove('is-resizing');
            }

            imageResizeState = null;
            updateImageControlState();
            positionImageResizeOverlay();
            synchronizeContent();
        }

        function ensureImageDropIndicator() {
            if (!imageDropIndicator) {
                imageDropIndicator = document.createElement('div');
                imageDropIndicator.className = 'diary-image-drop-indicator';
                imageDropIndicator.setAttribute('aria-hidden', 'true');
            }

            return imageDropIndicator;
        }

        function removeImageDropIndicator() {
            if (imageDropIndicator && imageDropIndicator.parentNode) {
                imageDropIndicator.parentNode.removeChild(imageDropIndicator);
            }
        }

        function editorNodeRectangle(node) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                return node.getBoundingClientRect();
            }

            var nodeRange = document.createRange();
            nodeRange.selectNodeContents(node);
            return nodeRange.getBoundingClientRect();
        }

        function findImageDropReference(pointerY) {
            var candidates = Array.prototype.filter.call(editor.childNodes, function (node) {
                if (
                    node === draggedMedia
                    || node === imageDropIndicator
                    || (node.nodeType === Node.TEXT_NODE && !node.nodeValue.trim())
                ) {
                    return false;
                }

                return node.nodeType === Node.ELEMENT_NODE
                    || node.nodeType === Node.TEXT_NODE;
            });

            for (var candidateIndex = 0; candidateIndex < candidates.length; candidateIndex += 1) {
                var candidate = candidates[candidateIndex];
                var rectangle = editorNodeRectangle(candidate);

                if (pointerY < rectangle.top + (rectangle.height / 2)) {
                    return candidate;
                }
            }

            return null;
        }

        function positionImageDropIndicator(pointerY) {
            var indicator = ensureImageDropIndicator();
            var referenceNode = findImageDropReference(pointerY);

            if (referenceNode) {
                editor.insertBefore(indicator, referenceNode);
            } else {
                editor.appendChild(indicator);
            }
        }

        function transferHasType(dataTransfer, expectedType) {
            return dataTransfer
                && Array.prototype.indexOf.call(dataTransfer.types || [], expectedType) !== -1;
        }

        function externalDropContainsImage(event) {
            var transfer = event.dataTransfer;

            if (!transfer) {
                return false;
            }

            if (
                transfer.files && transfer.files.length > 0
                || transferHasType(transfer, 'text/uri-list')
            ) {
                return true;
            }

            if (transferHasType(transfer, 'text/html')) {
                return /<img\b/i.test(transfer.getData('text/html') || '');
            }

            return false;
        }

        function finishImageDrag() {
            if (draggedImage) {
                draggedImage.setAttribute('aria-grabbed', 'false');
            }
            if (draggedMedia) {
                draggedMedia.classList.remove('is-dragging');
            }

            removeImageDropIndicator();
            draggedImage = null;
            draggedMedia = null;
            positionImageResizeOverlay();
        }

        function insertDiaryImage(src, alt, insertionRange) {
            var safeSrc = normalizeDiaryImageSrc(src);
            if (!safeSrc) {
                throw new Error('The uploaded image path was not accepted.');
            }

            var selection = window.getSelection();

            if (
                insertionRange
                && editor.contains(insertionRange.commonAncestorContainer)
                && selection
            ) {
                selection.removeAllRanges();
                selection.addRange(insertionRange);
            } else {
                restoreSelection();
                selection = window.getSelection();
            }

            var range;

            if (
                selection
                && selection.rangeCount > 0
                && editor.contains(selection.getRangeAt(0).commonAncestorContainer)
            ) {
                range = selection.getRangeAt(0);
            } else {
                range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
            }

            var image = document.createElement('img');
            image.src = safeSrc;
            image.alt = normalizeImageAlt(alt);
            image.setAttribute('draggable', 'true');
            image.addEventListener('load', positionImageResizeOverlay);

            var figure = document.createElement('figure');
            figure.setAttribute('data-diary-size', 'medium');
            figure.setAttribute('data-diary-align', 'center');
            figure.setAttribute('data-diary-wrap', 'none');
            figure.appendChild(image);

            range.deleteContents();
            range.insertNode(figure);

            var lineBreak = document.createElement('br');
            range.setStartAfter(figure);
            range.collapse(true);
            range.insertNode(lineBreak);
            range.setStartAfter(lineBreak);
            range.collapse(true);

            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
            selectDiaryImage(image);
            editor.focus();
        }

        function insertDiaryDrawing(src, alt, insertionRange) {
            var safeSrc = normalizeDiaryDrawingSrc(src);
            if (!safeSrc) {
                throw new Error('The uploaded drawing path was not accepted.');
            }

            var figure = document.createElement('figure');
            setDrawingMetadata(figure, {
                x: 50,
                y: 35,
                width: 35,
                rotation: 0
            });
            figure.setAttribute('contenteditable', 'false');

            var image = document.createElement('img');
            image.src = safeSrc;
            image.alt = normalizeImageAlt(alt || 'Journal drawing');
            image.removeAttribute('draggable');
            figure.appendChild(image);
            editor.appendChild(figure);
            applyDrawingPresentation(figure);

            image.addEventListener('load', function () {
                clampDrawingToEditor(figure);
                positionImageResizeOverlay();
            });

            clampDrawingToEditor(figure);
            synchronizeContent();
            focusEditorAtRange(insertionRange || null);
        }

        function uploadDiaryImage(file, uploadKind, insertionRange) {
            var allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            var maximumBytes = 5 * 1024 * 1024;
            var isDrawingUpload = uploadKind === 'drawing';
            var uploadLabel = isDrawingUpload ? 'drawing' : 'image';

            if (!file) {
                return;
            }

            if (file.size < 1) {
                setImageStatus('The selected image is invalid or corrupted.', true);
                return;
            }

            if (file.size > maximumBytes) {
                setImageStatus('The image is too large.', true);
                return;
            }

            if (file.type && allowedMimeTypes.indexOf(file.type.toLowerCase()) === -1) {
                setImageStatus('Only JPG, PNG, and WebP images are allowed.', true);
                return;
            }

            if (!window.fetch || !window.FormData) {
                setImageStatus('Image upload is not supported by this browser.', true);
                return;
            }

            var csrfInput = form.querySelector('input[name="csrf_token"]');
            var diaryIdInput = form.querySelector('input[name="id"]');
            var formData = new FormData();

            formData.append(isDrawingUpload ? 'diary_drawing_upload' : 'diary_image_upload', '1');
            formData.append(
                'diary_image',
                file,
                isDrawingUpload ? 'journal-drawing.png' : (file.name || 'journal-image')
            );
            formData.append('csrf_token', csrfInput ? csrfInput.value : '');

            if (diaryIdInput) {
                formData.append('id', diaryIdInput.value);
            }

            var uploadButton = isDrawingUpload && drawButton ? drawButton : imageButton;
            uploadButton.disabled = true;
            uploadButton.classList.add('is-uploading');
            setImageStatus('Uploading ' + uploadLabel + '…', false);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json()
                        .catch(function () {
                            return {};
                        })
                        .then(function (payload) {
                            if (!response.ok || !payload.ok) {
                                throw new Error(payload.error || 'Image upload failed. Please try again.');
                            }

                            return payload;
                        });
                })
                .then(function (payload) {
                    if (isDrawingUpload) {
                        insertDiaryDrawing(payload.src, payload.alt, insertionRange || null);
                    } else {
                        insertDiaryImage(payload.src, payload.alt, insertionRange || null);
                    }
                    setImageStatus(
                        (isDrawingUpload ? 'Drawing' : 'Image') + ' inserted into your journal entry.',
                        false
                    );
                })
                .catch(function (error) {
                    setImageStatus(
                        error.message || (isDrawingUpload
                            ? 'Drawing upload failed. Please try again.'
                            : 'Image upload failed. Please try again.'),
                        true
                    );
                })
                .then(function () {
                    uploadButton.disabled = false;
                    uploadButton.classList.remove('is-uploading');
                    imageInput.value = '';
                });
        }

        function runCommand(command, value, sizeOverride) {
            restoreSelection();
            document.execCommand('styleWithCSS', false, false);
            var commandApplied = document.execCommand(command, false, value || null);
            normalizeUnderlineElements();
            normalizeHighlightElements();
            normalizeFontElements(sizeOverride || null);
            rememberSelection();
            synchronizeContent();
            positionImageResizeOverlay();
            editor.focus();
            updateToolbarState();
            return commandApplied;
        }

        commandButtons.forEach(function (commandButton) {
            commandButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            commandButton.addEventListener('click', function () {
                runCommand(
                    commandButton.getAttribute('data-editor-command'),
                    commandButton.getAttribute('data-editor-value')
                );
            });
        });

        colorButtons.forEach(function (colorButton) {
            colorButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            colorButton.addEventListener('click', function () {
                runCommand('foreColor', colorButton.getAttribute('data-editor-color'));
            });
        });

        highlightButtons.forEach(function (highlightButton) {
            highlightButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            highlightButton.addEventListener('click', function () {
                var highlightColor = highlightButton.getAttribute('data-editor-highlight');

                if (!runCommand('hiliteColor', highlightColor)) {
                    runCommand('backColor', highlightColor);
                }
            });
        });

        if (imageButton && imageInput) {
            imageButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            imageButton.addEventListener('click', function () {
                imageInput.value = '';
                imageInput.click();
            });

            imageInput.addEventListener('change', function () {
                uploadDiaryImage(imageInput.files && imageInput.files[0]);
            });
        }

        if (emojiButton && emojiPicker) {
            emojiButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
                rememberSelection();
            });

            emojiButton.addEventListener('click', function () {
                var shouldOpen = emojiPicker.hidden;
                emojiPicker.hidden = !shouldOpen;
                emojiButton.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            });

            emojiOptions.forEach(function (emojiOption) {
                emojiOption.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                });

                emojiOption.addEventListener('click', function () {
                    insertEmojiAtCursor(emojiOption.getAttribute('data-editor-emoji') || '');
                    closeEmojiPicker();
                    editor.focus();
                });
            });

            document.addEventListener('click', function (event) {
                if (
                    !emojiPicker.hidden
                    && !emojiPicker.contains(event.target)
                    && !emojiButton.contains(event.target)
                ) {
                    closeEmojiPicker();
                }
            });
        }

        if (drawButton) {
            drawButton.addEventListener('mousedown', function (event) {
                event.preventDefault();
                rememberSelection();
                drawingInsertionRange = savedRange ? savedRange.cloneRange() : null;
            });

            drawButton.addEventListener('click', function () {
                if (!drawingInsertionRange) {
                    rememberSelection();
                    drawingInsertionRange = savedRange ? savedRange.cloneRange() : null;
                }
            });

            richEditor.addEventListener('diary:drawing-ready', function (event) {
                var drawingBlob = event.detail && event.detail.blob
                    ? event.detail.blob
                    : null;
                var insertionRange = drawingInsertionRange
                    ? drawingInsertionRange.cloneRange()
                    : null;

                drawingInsertionRange = null;
                uploadDiaryImage(drawingBlob, 'drawing', insertionRange);
            });
        }

        imageSizeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    clearDiaryImageSelection();
                    return;
                }

                var size = normalizeImageSize(
                    button.getAttribute('data-editor-image-size')
                );
                if (!size) {
                    return;
                }

                var layoutElement = imageLayoutElement(selectedImage);
                layoutElement.setAttribute('data-diary-size', size);
                layoutElement.style.removeProperty('width');
                if (!layoutElement.getAttribute('style')) {
                    layoutElement.removeAttribute('style');
                }
                updateImageControlState();
                positionImageResizeOverlay();
                synchronizeContent();
            });
        });

        imageAlignButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    clearDiaryImageSelection();
                    return;
                }

                var alignment = normalizeImageAlignment(
                    button.getAttribute('data-editor-image-align')
                );
                if (!alignment) {
                    return;
                }

                imageLayoutElement(selectedImage).setAttribute('data-diary-align', alignment);
                updateImageControlState();
                positionImageResizeOverlay();
                synchronizeContent();
            });
        });

        imageWrapButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    clearDiaryImageSelection();
                    return;
                }

                var wrapMode = normalizeImageWrap(
                    button.getAttribute('data-editor-image-wrap')
                );
                if (!wrapMode) {
                    return;
                }

                imageLayoutElement(selectedImage).setAttribute('data-diary-wrap', wrapMode);
                updateImageControlState();
                positionImageResizeOverlay();
                synchronizeContent();
            });
        });

        if (imageAltInput) {
            imageAltInput.addEventListener('input', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    return;
                }

                var safeAlt = imageAltInput.value
                    .replace(/[\u0000-\u001F\u007F]+/g, ' ')
                    .slice(0, 150);
                if (imageAltInput.value !== safeAlt) {
                    imageAltInput.value = safeAlt;
                }
                selectedImage.setAttribute('alt', safeAlt);
                synchronizeContent();
            });
        }

        if (imageCaptionInput) {
            imageCaptionInput.addEventListener('input', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    return;
                }

                var safeCaption = imageCaptionInput.value
                    .replace(/[\u0000-\u001F\u007F]+/g, ' ')
                    .slice(0, 250);
                if (imageCaptionInput.value !== safeCaption) {
                    imageCaptionInput.value = safeCaption;
                }

                var figure = ensureImageFigure(selectedImage);
                var caption = imageCaptionElement(selectedImage);
                if (safeCaption.trim() === '') {
                    if (caption) {
                        caption.remove();
                    }
                } else {
                    if (!caption) {
                        caption = document.createElement('figcaption');
                        caption.setAttribute('contenteditable', 'false');
                        figure.appendChild(caption);
                    }
                    caption.textContent = safeCaption;
                }

                synchronizeContent();
            });
        }

        if (imageRemoveButton) {
            imageRemoveButton.addEventListener('click', function () {
                if (!selectedImage || !editor.contains(selectedImage)) {
                    clearDiaryImageSelection();
                    return;
                }

                var removedMedia = imageLayoutElement(selectedImage);
                clearDiaryImageSelection();
                removedMedia.remove();
                synchronizeContent();
                editor.focus();
            });
        }

        function beginDrawingMove(event, figure) {
            if (
                (event.pointerType === 'mouse' && event.button !== 0)
                || imageResizeState
                || !isDiaryDrawingFigure(figure)
            ) {
                return;
            }

            var metadata = drawingMetadata(figure);
            var image = figure.querySelector('img');
            if (!metadata || !image || !normalizeDiaryDrawingSrc(image.getAttribute('src'))) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            selectDiaryImage(image);
            drawingMoveState = {
                pointerId: event.pointerId,
                figure: figure,
                startClientX: event.clientX,
                startClientY: event.clientY,
                startX: metadata.x,
                startY: metadata.y
            };
            figure.classList.add('is-moving');

            if (figure.setPointerCapture) {
                figure.setPointerCapture(event.pointerId);
            }
        }

        function moveDrawing(event) {
            if (!drawingMoveState || event.pointerId !== drawingMoveState.pointerId) {
                return;
            }

            event.preventDefault();
            var figure = drawingMoveState.figure;
            var metadata = drawingMetadata(figure);
            if (!metadata || editor.clientWidth < 1 || editor.clientHeight < 1) {
                return;
            }

            metadata.x = drawingMoveState.startX
                + ((event.clientX - drawingMoveState.startClientX) / editor.clientWidth * 100);
            metadata.y = drawingMoveState.startY
                + ((event.clientY - drawingMoveState.startClientY) / editor.clientHeight * 100);
            metadata.x = Math.min(100, Math.max(0, metadata.x));
            metadata.y = Math.min(100, Math.max(0, metadata.y));
            setDrawingMetadata(figure, metadata);
            clampDrawingToEditor(figure);
            positionImageResizeOverlay();
        }

        function finishDrawingMove(event) {
            if (!drawingMoveState || event.pointerId !== drawingMoveState.pointerId) {
                return;
            }

            var figure = drawingMoveState.figure;
            if (
                figure.releasePointerCapture
                && figure.hasPointerCapture
                && figure.hasPointerCapture(event.pointerId)
            ) {
                figure.releasePointerCapture(event.pointerId);
            }

            figure.classList.remove('is-moving');
            drawingMoveState = null;
            clampDrawingToEditor(figure);
            positionImageResizeOverlay();
            synchronizeContent();
        }

        if (drawingRotationInput) {
            drawingRotationInput.addEventListener('input', function () {
                var figure = selectedImage ? drawingFigureForImage(selectedImage) : null;
                var metadata = figure ? drawingMetadata(figure) : null;
                var rotation = normalizeDrawingRotation(drawingRotationInput.value);
                if (!figure || !metadata || rotation === null) {
                    return;
                }

                metadata.rotation = rotation;
                setDrawingMetadata(figure, metadata);
                clampDrawingToEditor(figure);
                drawingRotationValue.value = rotation + '°';
                drawingRotationValue.textContent = rotation + '°';
                positionImageResizeOverlay();
                synchronizeContent();
            });
        }

        if (drawingRemoveButton) {
            drawingRemoveButton.addEventListener('click', function () {
                var figure = selectedImage ? drawingFigureForImage(selectedImage) : null;
                if (!figure) {
                    clearDiaryImageSelection();
                    return;
                }

                clearDiaryImageSelection();
                figure.remove();
                synchronizeContent();
                editor.focus();
            });
        }

        editor.addEventListener('pointerdown', function (event) {
            var figure = event.target && event.target.closest
                ? event.target.closest('figure[data-diary-object="drawing"]')
                : null;

            if (figure && editor.contains(figure)) {
                if (!drawingHasVisibleInkAtPoint(figure, event.clientX, event.clientY)) {
                    event.preventDefault();
                    event.stopPropagation();
                    passDrawingClickThroughToEditor(event, figure);
                    return;
                }

                ignoredDrawingClick = null;
                ignoredDrawingClickUntil = 0;
                beginDrawingMove(event, figure);
            }
        });

        document.addEventListener('pointermove', moveDrawing);
        document.addEventListener('pointerup', finishDrawingMove);
        document.addEventListener('pointercancel', finishDrawingMove);

        imageResizeHandles.forEach(function (handle) {
            handle.addEventListener('pointerdown', function (event) {
                beginImageResize(event, handle);
            });
        });

        document.addEventListener('pointermove', moveImageResize);
        document.addEventListener('pointerup', finishImageResize);
        document.addEventListener('pointercancel', finishImageResize);

        editor.addEventListener('click', function (event) {
            var clickedFigure = event.target && event.target.closest
                ? event.target.closest('figure')
                : null;
            var clickedImage = event.target && event.target.tagName === 'IMG'
                ? event.target
                : (clickedFigure ? clickedFigure.querySelector('img') : null);

            if (
                isDiaryDrawingFigure(clickedFigure)
                && clickedFigure === ignoredDrawingClick
                && Date.now() <= ignoredDrawingClickUntil
            ) {
                event.preventDefault();
                event.stopPropagation();
                ignoredDrawingClick = null;
                ignoredDrawingClickUntil = 0;
                return;
            }

            if (clickedImage && editor.contains(clickedImage)) {
                event.preventDefault();
                selectDiaryImage(clickedImage);
                return;
            }

            clearDiaryImageSelection();
        });

        document.addEventListener('click', function (event) {
            if (
                !selectedImage
                || editor.contains(event.target)
                || (imageControls && imageControls.contains(event.target))
                || (drawingControls && drawingControls.contains(event.target))
                || (imageResizeOverlay && imageResizeOverlay.contains(event.target))
            ) {
                return;
            }

            clearDiaryImageSelection();
        });

        editor.addEventListener('dragstart', function (event) {
            var image = event.target && event.target.tagName === 'IMG'
                ? event.target
                : null;

            if (
                !image
                || drawingFigureForImage(image)
                || !normalizeDiaryImageSrc(image.getAttribute('src'))
            ) {
                event.preventDefault();
                return;
            }

            draggedImage = image;
            draggedMedia = imageLayoutElement(image);
            selectDiaryImage(image);
            draggedMedia.classList.add('is-dragging');
            image.setAttribute('aria-grabbed', 'true');

            if (imageResizeOverlay) {
                imageResizeOverlay.hidden = true;
            }

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', 'diary-image');
            }
        });

        editor.addEventListener('dragover', function (event) {
            if (draggedImage && draggedMedia && editor.contains(draggedMedia)) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                positionImageDropIndicator(event.clientY);
                return;
            }

            if (
                transferHasType(event.dataTransfer, 'Files')
                || transferHasType(event.dataTransfer, 'text/uri-list')
            ) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'none';
            }
        });

        editor.addEventListener('dragleave', function (event) {
            if (
                draggedImage
                && (!event.relatedTarget || !editor.contains(event.relatedTarget))
            ) {
                removeImageDropIndicator();
            }
        });

        editor.addEventListener('drop', function (event) {
            if (draggedImage && editor.contains(draggedImage)) {
                event.preventDefault();
                event.stopPropagation();

                if (!imageDropIndicator || !imageDropIndicator.parentNode) {
                    positionImageDropIndicator(event.clientY);
                }

                imageDropIndicator.parentNode.insertBefore(draggedMedia, imageDropIndicator);
                removeImageDropIndicator();
                draggedMedia.classList.remove('is-dragging');
                draggedImage.setAttribute('aria-grabbed', 'false');
                var droppedImage = draggedImage;
                draggedImage = null;
                draggedMedia = null;
                selectDiaryImage(droppedImage);
                synchronizeContent();
                return;
            }

            if (externalDropContainsImage(event)) {
                event.preventDefault();
                event.stopPropagation();
                removeImageDropIndicator();
                setImageStatus('Use the Image button to upload JPG, PNG, or WebP files safely.', true);
            }
        });

        editor.addEventListener('dragend', finishImageDrag);

        if (fontFamilySelect) {
            fontFamilySelect.addEventListener('change', function () {
                if (fontFamilySelect.value !== '') {
                    runCommand('fontName', fontFamilySelect.value);
                }
            });
        }

        if (fontSizeSelect) {
            fontSizeSelect.addEventListener('change', function () {
                if (fontSizeSelect.value !== '') {
                    runCommand('fontSize', '7', fontSizeSelect.value);
                }
            });
        }

        editor.addEventListener('input', function () {
            rememberSelection();
            synchronizeContent();
            positionImageResizeOverlay();
            updateToolbarState();
        });

        editor.addEventListener('keyup', function () {
            rememberSelection();
            updateToolbarState();
        });
        editor.addEventListener('mouseup', function () {
            rememberSelection();
            scheduleToolbarStateUpdate();
        });
        editor.addEventListener('focus', function () {
            rememberSelection();
            updateToolbarState();
        });
        editor.addEventListener('click', scheduleToolbarStateUpdate);
        document.addEventListener('selectionchange', function () {
            if (selectionIsInsideEditor()) {
                rememberSelection();
                updateToolbarState();
            }
        });

        editor.addEventListener('paste', function (event) {
            var clipboard = event.clipboardData || window.clipboardData;

            if (!clipboard) {
                return;
            }

            event.preventDefault();
            document.execCommand('insertText', false, clipboard.getData('text/plain'));
            rememberSelection();
            synchronizeContent();
        });

        form.addEventListener('submit', synchronizeContent);
        window.addEventListener('resize', function () {
            editor.querySelectorAll('figure[data-diary-object="drawing"]').forEach(function (drawing) {
                clampDrawingToEditor(drawing);
            });
            positionImageResizeOverlay();
        });
        window.addEventListener('scroll', positionImageResizeOverlay, true);
        loadInitialContent(contentField.value);
        prepareEditorImages();
        synchronizeContent();
        updateToolbarState();
    });
}());

(function () {
    'use strict';

    var moodPickers = document.querySelectorAll('[data-diary-mood-picker]');

    moodPickers.forEach(function (moodPicker) {
        var form = moodPicker.closest('form');
        var moodInput = form ? form.querySelector('input[name="mood"]') : null;
        var moodButtons = Array.prototype.slice.call(
            moodPicker.querySelectorAll('.diary-mood-option')
        );
        var errorMessage = form ? form.querySelector('.diary-mood-client-error') : null;

        if (!form || !moodInput || moodButtons.length === 0) {
            return;
        }

        function selectMood(selectedButton, moveFocus) {
            moodInput.value = selectedButton.getAttribute('data-mood') || '';

            moodButtons.forEach(function (moodButton) {
                var isSelected = moodButton === selectedButton;
                moodButton.classList.toggle('is-selected', isSelected);
                moodButton.setAttribute('aria-checked', isSelected ? 'true' : 'false');
            });

            moodPicker.classList.remove('has-error');

            if (errorMessage) {
                errorMessage.hidden = true;
            }

            if (moveFocus) {
                selectedButton.focus();
            }
        }

        moodButtons.forEach(function (moodButton, buttonIndex) {
            moodButton.addEventListener('click', function () {
                selectMood(moodButton, false);
            });

            moodButton.addEventListener('keydown', function (event) {
                var targetIndex = null;

                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                    targetIndex = (buttonIndex + 1) % moodButtons.length;
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    targetIndex = (buttonIndex - 1 + moodButtons.length) % moodButtons.length;
                } else if (event.key === 'Home') {
                    targetIndex = 0;
                } else if (event.key === 'End') {
                    targetIndex = moodButtons.length - 1;
                }

                if (targetIndex !== null) {
                    event.preventDefault();
                    selectMood(moodButtons[targetIndex], true);
                }
            });
        });

        form.addEventListener('submit', function (event) {
            if (moodInput.value !== '') {
                return;
            }

            event.preventDefault();
            moodPicker.classList.add('has-error');

            if (errorMessage) {
                errorMessage.hidden = false;
            }

            moodButtons[0].focus();
        });
    });
}());
(function () {
    'use strict';

    var deleteForms = Array.prototype.slice.call(
        document.querySelectorAll('form[action="delete_handler.php"][method="post"]')
    );

    if (deleteForms.length === 0) {
        return;
    }

    var activeForm = null;
    var confirmedForm = null;
    var previousFocus = null;
    var style = document.createElement('style');
    var overlay = document.createElement('div');

    style.textContent = [
        '.diary-delete-confirmation-overlay{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(52,46,40,.52);}',
        '.diary-delete-confirmation-overlay[hidden]{display:none;}',
        '.diary-delete-confirmation-dialog{width:min(440px,100%);padding:28px;border:1px solid var(--diary-border);border-radius:18px;background:var(--diary-paper);box-shadow:var(--diary-shadow);color:var(--diary-text);text-align:center;}',
        '.diary-delete-confirmation-dialog h2{margin:0 0 10px;color:var(--diary-accent-dark);font-size:1.4rem;}',
        '.diary-delete-confirmation-dialog p{margin:0;color:var(--diary-muted);line-height:1.65;}',
        '.diary-delete-confirmation-actions{display:flex;justify-content:center;gap:12px;margin-top:24px;flex-wrap:wrap;}',
        '.diary-delete-confirmation-actions button{min-width:110px;}',
        'body.diary-confirmation-open{overflow:hidden;}'
    ].join('');
    document.head.appendChild(style);

    overlay.className = 'diary-delete-confirmation-overlay';
    overlay.hidden = true;
    overlay.innerHTML = [
        '<section class="diary-delete-confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="diary-delete-confirmation-title" aria-describedby="diary-delete-confirmation-message">',
        '<h2 id="diary-delete-confirmation-title">Delete Journal Entry?</h2>',
        '<p id="diary-delete-confirmation-message">Are you sure you want to delete this journal entry?</p>',
        '<div class="diary-delete-confirmation-actions">',
        '<button class="diary-action-button diary-action-secondary" type="button" data-diary-delete-cancel>Cancel</button>',
        '<button class="diary-action-button diary-action-delete" type="button" data-diary-delete-confirm>Delete</button>',
        '</div>',
        '</section>'
    ].join('');
    document.body.appendChild(overlay);

    var cancelButton = overlay.querySelector('[data-diary-delete-cancel]');
    var confirmButton = overlay.querySelector('[data-diary-delete-confirm]');

    function closeConfirmation() {
        overlay.hidden = true;
        document.body.classList.remove('diary-confirmation-open');
        activeForm = null;

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
        previousFocus = null;
    }

    function openConfirmation(form) {
        activeForm = form;
        previousFocus = document.activeElement;
        overlay.hidden = false;
        document.body.classList.add('diary-confirmation-open');
        cancelButton.focus();
    }

    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (confirmedForm === form) {
                confirmedForm = null;
                return;
            }

            event.preventDefault();
            openConfirmation(form);
        });
    });

    cancelButton.addEventListener('click', closeConfirmation);

    confirmButton.addEventListener('click', function () {
        var formToSubmit = activeForm;

        if (!formToSubmit) {
            closeConfirmation();
            return;
        }

        confirmedForm = formToSubmit;
        closeConfirmation();

        if (typeof formToSubmit.requestSubmit === 'function') {
            formToSubmit.requestSubmit();
        } else {
            formToSubmit.submit();
        }
    });

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeConfirmation();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (overlay.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeConfirmation();
        }
    });
}());
(function () {
    'use strict';

    var drawButtons = document.querySelectorAll('[data-editor-draw]');

    drawButtons.forEach(function (drawButton, drawingIndex) {
        var modal = document.createElement('div');
        var drawingId = 'diary-drawing-title-' + drawingIndex;

        modal.className = 'diary-drawing-modal';
        modal.id = 'diary-drawing-modal-' + drawingIndex;
        modal.setAttribute('data-diary-drawing-modal', '');
        modal.hidden = true;
        drawButton.setAttribute('aria-controls', modal.id);
        modal.innerHTML = [
            '<section class="diary-drawing-dialog" role="dialog" aria-modal="true" aria-labelledby="' + drawingId + '">',
            '<h2 id="' + drawingId + '">Draw in Your Journal</h2>',
            '<p class="diary-drawing-intro">Create a quick sketch on a clean canvas.</p>',
            '<div class="diary-drawing-controls">',
            '<label class="diary-drawing-color">Pen color <input type="color" value="#342e28" data-diary-drawing-color aria-label="Pen color"></label>',
            '<div class="diary-drawing-thickness" role="group" aria-label="Pen thickness">',
            '<button type="button" data-diary-drawing-width="2" aria-pressed="false">Thin</button>',
            '<button class="is-active" type="button" data-diary-drawing-width="5" aria-pressed="true">Medium</button>',
            '<button type="button" data-diary-drawing-width="9" aria-pressed="false">Thick</button>',
            '</div>',
            '<button class="diary-drawing-utility-button" type="button" data-diary-drawing-undo disabled>Undo</button>',
            '<button class="diary-drawing-utility-button" type="button" data-diary-drawing-clear disabled>Clear</button>',
            '</div>',
            '<canvas class="diary-drawing-canvas" width="900" height="500" data-diary-drawing-canvas aria-label="Blank drawing canvas"></canvas>',
            '<p class="diary-drawing-note" data-diary-drawing-note>Your drawing will be uploaded as a PNG when you choose Use Drawing.</p>',
            '<div class="diary-drawing-actions">',
            '<button class="diary-action-button diary-action-secondary" type="button" data-diary-drawing-cancel>Cancel</button>',
            '<button class="diary-action-button diary-action-primary" type="button" data-diary-drawing-use disabled>Use Drawing</button>',
            '</div>',
            '</section>'
        ].join('');
        document.body.appendChild(modal);

        var canvas = modal.querySelector('[data-diary-drawing-canvas]');
        var context = canvas.getContext('2d');
        var colorInput = modal.querySelector('[data-diary-drawing-color]');
        var widthButtons = Array.prototype.slice.call(
            modal.querySelectorAll('[data-diary-drawing-width]')
        );
        var undoButton = modal.querySelector('[data-diary-drawing-undo]');
        var clearButton = modal.querySelector('[data-diary-drawing-clear]');
        var cancelButton = modal.querySelector('[data-diary-drawing-cancel]');
        var useButton = modal.querySelector('[data-diary-drawing-use]');
        var drawingNote = modal.querySelector('[data-diary-drawing-note]');
        var defaultDrawingNote = drawingNote.textContent;
        var targetEditor = drawButton.closest('[data-diary-rich-editor]');
        var strokes = [];
        var activeStroke = null;
        var activePointerId = null;
        var penWidth = 5;
        var previousFocus = null;

        context.lineCap = 'round';
        context.lineJoin = 'round';

        function canvasPoint(event) {
            var rectangle = canvas.getBoundingClientRect();

            return {
                x: (event.clientX - rectangle.left) * (canvas.width / rectangle.width),
                y: (event.clientY - rectangle.top) * (canvas.height / rectangle.height)
            };
        }

        function drawStroke(stroke) {
            if (!stroke || stroke.points.length === 0) {
                return;
            }

            context.strokeStyle = stroke.color;
            context.fillStyle = stroke.color;
            context.lineWidth = stroke.width;
            context.lineCap = 'round';
            context.lineJoin = 'round';

            if (stroke.points.length === 1) {
                context.beginPath();
                context.arc(
                    stroke.points[0].x,
                    stroke.points[0].y,
                    stroke.width / 2,
                    0,
                    Math.PI * 2
                );
                context.fill();
                return;
            }

            context.beginPath();
            context.moveTo(stroke.points[0].x, stroke.points[0].y);

            stroke.points.slice(1).forEach(function (point) {
                context.lineTo(point.x, point.y);
            });

            context.stroke();
        }

        function redrawCanvas() {
            context.clearRect(0, 0, canvas.width, canvas.height);
            strokes.forEach(drawStroke);
        }

        function updateDrawingButtons() {
            var hasStrokes = strokes.length > 0;
            undoButton.disabled = !hasStrokes;
            clearButton.disabled = !hasStrokes;
            useButton.disabled = !hasStrokes;
        }

        function resetDrawing() {
            strokes = [];
            activeStroke = null;
            activePointerId = null;
            redrawCanvas();
            updateDrawingButtons();
        }

        function closeDrawingModal(discardDrawing) {
            if (discardDrawing) {
                resetDrawing();
            }

            modal.hidden = true;
            drawButton.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('diary-drawing-open');

            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }
            previousFocus = null;
        }

        function finishStroke(event) {
            if (activePointerId === null || event.pointerId !== activePointerId) {
                return;
            }

            if (canvas.hasPointerCapture(activePointerId)) {
                canvas.releasePointerCapture(activePointerId);
            }

            activeStroke = null;
            activePointerId = null;
            updateDrawingButtons();
        }

        drawButton.addEventListener('click', function () {
            previousFocus = drawButton;
            drawingNote.textContent = defaultDrawingNote;
            drawingNote.classList.remove('is-error');
            modal.hidden = false;
            drawButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('diary-drawing-open');
            colorInput.focus();
        });

        widthButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                penWidth = Number(button.getAttribute('data-diary-drawing-width')) || 5;

                widthButtons.forEach(function (widthButton) {
                    var isSelected = widthButton === button;
                    widthButton.classList.toggle('is-active', isSelected);
                    widthButton.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                });
            });
        });

        canvas.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            event.preventDefault();
            activePointerId = event.pointerId;
            activeStroke = {
                color: colorInput.value || '#342e28',
                width: penWidth,
                points: [canvasPoint(event)]
            };
            strokes.push(activeStroke);
            canvas.setPointerCapture(activePointerId);
            drawStroke(activeStroke);
            updateDrawingButtons();
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!activeStroke || event.pointerId !== activePointerId) {
                return;
            }

            event.preventDefault();
            var previousPoint = activeStroke.points[activeStroke.points.length - 1];
            var nextPoint = canvasPoint(event);
            activeStroke.points.push(nextPoint);

            context.strokeStyle = activeStroke.color;
            context.lineWidth = activeStroke.width;
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.beginPath();
            context.moveTo(previousPoint.x, previousPoint.y);
            context.lineTo(nextPoint.x, nextPoint.y);
            context.stroke();
        });

        canvas.addEventListener('pointerup', finishStroke);
        canvas.addEventListener('pointercancel', finishStroke);

        undoButton.addEventListener('click', function () {
            if (strokes.length === 0) {
                return;
            }

            strokes.pop();
            redrawCanvas();
            updateDrawingButtons();
        });

        clearButton.addEventListener('click', resetDrawing);
        cancelButton.addEventListener('click', function () {
            closeDrawingModal(true);
        });
        useButton.addEventListener('click', function () {
            if (strokes.length === 0 || !targetEditor) {
                return;
            }

            useButton.disabled = true;
            useButton.textContent = 'Preparing…';

            canvas.toBlob(function (drawingBlob) {
                useButton.textContent = 'Use Drawing';
                updateDrawingButtons();

                if (!drawingBlob) {
                    drawingNote.textContent = 'The drawing could not be prepared. Please try again.';
                    drawingNote.classList.add('is-error');
                    return;
                }

                closeDrawingModal(false);
                targetEditor.dispatchEvent(new CustomEvent('diary:drawing-ready', {
                    detail: {
                        blob: drawingBlob
                    }
                }));
            }, 'image/png');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeDrawingModal(true);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!modal.hidden && event.key === 'Escape') {
                event.preventDefault();
                closeDrawingModal(true);
            }
        });
    });
}());
