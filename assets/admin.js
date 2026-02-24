/**
 * IAPOST Groq — Admin JavaScript
 */
(function ($) {
    'use strict';

    var IAPOSTGROQ_UI = {

        _noticia:         '',
        _enfoque:         '',
        _content_type:    '',
        _journalist_role: '',

        _redirectTimer: null,

        init: function () {
            this.bindEvents();
            this.initCharCounters();
            this.initMetaBar();
            this.initPromptbookPreviews();
            this.initCustomPromptbooks();
            this.initTagsAutocomplete();
            this.initEditorTabs();
        },

        // ── Events ────────────────────────────────────────────────────────────

        bindEvents: function () {
            // Generator
            $('#iapostgroq-btn-generate').on('click', this.onGenerate.bind(this));
            $('#iapostgroq-btn-rewrite').on('click', this.onOpenModal.bind(this));
            $('#iapostgroq-btn-publish').on('click', this.onPublish.bind(this));

            // Modal
            $('#iapostgroq-modal-apply').on('click', this.onRewrite.bind(this));
            $('#iapostgroq-modal-cancel').on('click', this.closeModal.bind(this));
            $('#iapostgroq-modal-overlay').on('click', function (e) {
                if ($(e.target).is('#iapostgroq-modal-overlay')) {
                    IAPOSTGROQ_UI.closeModal();
                }
            });

            // Settings — API tab
            $('#iapostgroq-btn-save-settings').on('click', this.onSaveSettings.bind(this));
            $('#iapostgroq-btn-test-api').on('click', this.onTestApi.bind(this));
            $('#iapostgroq-btn-save-defaults').on('click', this.onSaveDefaults.bind(this));
        },

        // ── Character counters ─────────────────────────────────────────────────

        initCharCounters: function () {
            this.bindCounter('#iapostgroq-seo-title',            '#iapostgroq-seo-title-count',      60);
            this.bindCounter('#iapostgroq-metadesc',             '#iapostgroq-metadesc-count',       140);
            this.bindCounter('#iapostgroq-og-title',             '#iapostgroq-og-title-count',       60);
            this.bindCounter('#iapostgroq-og-description',       '#iapostgroq-og-desc-count',        160);
            this.bindCounter('#iapostgroq-twitter-title',        '#iapostgroq-twitter-title-count',  55);
            this.bindCounter('#iapostgroq-twitter-description',  '#iapostgroq-twitter-desc-count',   140);
        },

        bindCounter: function (fieldSel, counterSel, max) {
            var $field   = $(fieldSel);
            var $counter = $(counterSel);
            if (!$field.length) return;

            function update() {
                var len = $field.val().length;
                $counter.text('(' + len + '/' + max + ')');
                $counter.toggleClass('iapostgroq-over-limit', len > max);
            }
            $field.on('input keyup', update);
            update();
        },

        // ── Meta description progress bar ─────────────────────────────────────

        initMetaBar: function () {
            var $field = $('#iapostgroq-metadesc');
            var $bar   = $('#iapostgroq-metadesc-bar');
            if (!$field.length || !$bar.length) return;

            var MAX = 140;

            function update() {
                var len  = $field.val().length;
                var pct  = Math.min( (len / MAX) * 100, 100 );
                $bar.css('width', pct + '%');
                $bar.removeClass('warn over');
                if (len > MAX) {
                    $bar.addClass('over');
                } else if (len > MAX * 0.85) {
                    $bar.addClass('warn');
                }
            }
            $field.on('input keyup', update);
            update();
        },

        // ── Promptbook selector previews ──────────────────────────────────────

        initPromptbookPreviews: function () {
            this.bindPromptbookPreview('#iapostgroq-content-type',    '#iapostgroq-content-type-desc');
            this.bindPromptbookPreview('#iapostgroq-journalist-role', '#iapostgroq-journalist-role-desc');
        },

        bindPromptbookPreview: function (selectSel, descSel) {
            var $select = $(selectSel);
            var $desc   = $(descSel);
            if (!$select.length) return;

            function update() {
                var raw    = $select.find(':selected').data('desc') || '';
                var lines  = raw.split('\n');
                // Remove the "TIPO DE CONTENIDO:..." / "ROL:..." first line
                var display = lines.slice(1).join(' ').trim();
                $desc.text(display);
            }
            $select.on('change', update);
            update();
        },

        // ── Generate ──────────────────────────────────────────────────────────

        onGenerate: function () {
            var noticia         = $('#iapostgroq-noticia').val().trim();
            var enfoque         = $('#iapostgroq-enfoque').val().trim();
            var content_type    = $('#iapostgroq-content-type').val();
            var journalist_role = $('#iapostgroq-journalist-role').val();

            if (!noticia || !enfoque) {
                this.showError('Por favor, completa la noticia y el enfoque.');
                return;
            }

            this._noticia         = noticia;
            this._enfoque         = enfoque;
            this._content_type    = content_type;
            this._journalist_role = journalist_role;

            this.showError('');
            this.showSuccess('');
            this.setSpinner('#iapostgroq-spinner', true);
            this.setButton('#iapostgroq-btn-generate', true, IAPOSTGROQ.strings.generating);

            $.post(IAPOSTGROQ.ajax_url, {
                action:          'iapostgroq_generate',
                nonce:           IAPOSTGROQ.nonce,
                noticia:         noticia,
                enfoque:         enfoque,
                content_type:    content_type,
                journalist_role: journalist_role
            })
            .done(function (response) {
                if (response.success) {
                    IAPOSTGROQ_UI.populateResults(response.data);
                    $('#iapostgroq-results').slideDown(300);
                } else {
                    IAPOSTGROQ_UI.showError(response.data.message || IAPOSTGROQ.strings.error);
                }
            })
            .fail(function () {
                IAPOSTGROQ_UI.showError(IAPOSTGROQ.strings.error);
            })
            .always(function () {
                IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner', false);
                IAPOSTGROQ_UI.setButton('#iapostgroq-btn-generate', false, 'Generar Contenido');
            });
        },

        // ── Re-write ──────────────────────────────────────────────────────────

        onOpenModal: function () {
            $('#iapostgroq-modal-instructions').val('');
            $('#iapostgroq-modal-overlay').fadeIn(200);
        },

        closeModal: function () {
            $('#iapostgroq-modal-overlay').fadeOut(200);
        },

        onRewrite: function () {
            var extra = $('#iapostgroq-modal-instructions').val().trim();

            this.setSpinner('#iapostgroq-spinner-rewrite', true);
            this.setButton('#iapostgroq-modal-apply', true, IAPOSTGROQ.strings.rewriting);

            $.post(IAPOSTGROQ.ajax_url, {
                action:             'iapostgroq_rewrite',
                nonce:              IAPOSTGROQ.nonce,
                noticia:            this._noticia,
                enfoque:            this._enfoque,
                extra_instructions: extra,
                content_type:       this._content_type,
                journalist_role:    this._journalist_role
            })
            .done(function (response) {
                if (response.success) {
                    IAPOSTGROQ_UI.populateResults(response.data);
                    IAPOSTGROQ_UI.closeModal();
                } else {
                    IAPOSTGROQ_UI.showError(response.data.message || IAPOSTGROQ.strings.error);
                }
            })
            .fail(function () {
                IAPOSTGROQ_UI.showError(IAPOSTGROQ.strings.error);
            })
            .always(function () {
                IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner-rewrite', false);
                IAPOSTGROQ_UI.setButton('#iapostgroq-modal-apply', false, 'Aplicar');
            });
        },

        // ── Gutenberg editor tabs + live preview ──────────────────────────────

        initEditorTabs: function () {
            var self = this;

            $(document).on('click', '.iapostgroq-tab-btn', function () {
                var panel = $(this).data('panel');
                var $wrap = $(this).closest('.iapostgroq-editor-wrap');

                $wrap.find('.iapostgroq-tab-btn').removeClass('active');
                $(this).addClass('active');

                if (panel === 'preview') {
                    self.updatePreview();
                    $wrap.find('.iapostgroq-panel-code').hide();
                    $wrap.find('.iapostgroq-panel-preview').show();
                } else {
                    $wrap.find('.iapostgroq-panel-preview').hide();
                    $wrap.find('.iapostgroq-panel-code').show();
                }
            });

            // Update preview on every keystroke (debounced 400ms)
            var previewTimer = null;
            $(document).on('input keyup', '#iapostgroq-content', function () {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(function () {
                    if ($('.iapostgroq-panel-preview').is(':visible')) {
                        IAPOSTGROQ_UI.updatePreview();
                    }
                }, 400);
            });
        },

        updatePreview: function () {
            var raw = $('#iapostgroq-content').val() || '';
            var $preview = $('#iapostgroq-content-preview');

            if (!raw.trim()) {
                $preview.html('<p class="iapostgroq-preview-empty">Sin contenido todavía.</p>');
                return;
            }

            // Strip Gutenberg block comments, keep inner HTML
            var html = raw
                .replace(/<!--\s*wp:[^\-][\s\S]*?-->/g, '')
                .replace(/<!--\s*\/wp:[^\s>]+\s*-->/g, '')
                .trim();

            $preview.html(html);
        },

        // ── Tags autocomplete ─────────────────────────────────────────────────

        initTagsAutocomplete: function () {
            var $field = $('#iapostgroq-tags');
            if (!$field.length || typeof $.fn.autocomplete === 'undefined') return;

            function extractLast(term) {
                return term.split(',').pop().trim();
            }

            $field.autocomplete({
                minLength: 1,
                source: function (request, response) {
                    $.getJSON(IAPOSTGROQ.ajax_url, {
                        action: 'iapostgroq_search_tags',
                        nonce:  IAPOSTGROQ.nonce,
                        term:   extractLast(request.term)
                    }, response);
                },
                focus: function () {
                    return false; // Don't replace field on focus
                },
                select: function (event, ui) {
                    var terms = this.value.split(',').map(function (s) { return s.trim(); });
                    terms.pop();
                    terms.push(ui.item.value);
                    this.value = terms.join(', ') + ', ';
                    return false;
                }
            });
        },

        // ── Publish ───────────────────────────────────────────────────────────

        onPublish: function () {
            this.showError('');
            this.showSuccess('');
            this.setSpinner('#iapostgroq-spinner-publish', true);
            this.setButton('#iapostgroq-btn-publish', true, IAPOSTGROQ.strings.publishing);

            // Serialize multi-select as array
            var categories = $('#iapostgroq-categories').val() || [];

            $.post(IAPOSTGROQ.ajax_url, $.extend(
                {
                    action:               'iapostgroq_publish',
                    nonce:                IAPOSTGROQ.nonce,
                    title:                $('#iapostgroq-title').val(),
                    content:              $('#iapostgroq-content').val(),
                    slug:                 $('#iapostgroq-slug').val(),
                    keyword:              $('#iapostgroq-keyword').val(),
                    seo_title:            $('#iapostgroq-seo-title').val(),
                    meta_description:     $('#iapostgroq-metadesc').val(),
                    og_title:             $('#iapostgroq-og-title').val(),
                    og_description:       $('#iapostgroq-og-description').val(),
                    twitter_title:        $('#iapostgroq-twitter-title').val(),
                    twitter_description:  $('#iapostgroq-twitter-description').val(),
                    tags:                 $('#iapostgroq-tags').val()
                },
                this.serializeArray('categories', categories)
            ))
            .done(function (response) {
                if (response.success) {
                    IAPOSTGROQ_UI.showSuccessPopup(response.data.edit_url);
                } else {
                    IAPOSTGROQ_UI.showError(response.data.message || IAPOSTGROQ.strings.error);
                }
            })
            .fail(function () {
                IAPOSTGROQ_UI.showError(IAPOSTGROQ.strings.error);
            })
            .always(function () {
                IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner-publish', false);
                IAPOSTGROQ_UI.setButton('#iapostgroq-btn-publish', false, 'Enviar como Entrada');
            });
        },

        /** Convert ['1','2'] to {'categories[0]':'1', 'categories[1]':'2'} for $.post */
        serializeArray: function (name, arr) {
            var obj = {};
            if (!arr || !arr.length) return obj;
            $.each(arr, function (i, val) {
                obj[name + '[' + i + ']'] = val;
            });
            return obj;
        },

        // ── Settings: save & test ─────────────────────────────────────────────

        onSaveSettings: function () {
            this.setSpinner('#iapostgroq-spinner-settings', true);
            this.setButton('#iapostgroq-btn-save-settings', true, IAPOSTGROQ.strings.saving);

            $.post(IAPOSTGROQ.ajax_url, {
                action:  'iapostgroq_save_settings',
                nonce:   IAPOSTGROQ.nonce,
                api_key: $('#iapostgroq-api-key').val(),
                model:   $('#iapostgroq-model-select').val()
            })
            .done(function (response) {
                var msg  = response.data ? response.data.message : IAPOSTGROQ.strings.error;
                IAPOSTGROQ_UI.showSettingsMsg(msg, response.success ? 'notice-success' : 'notice-error');
            })
            .fail(function () {
                IAPOSTGROQ_UI.showSettingsMsg(IAPOSTGROQ.strings.error, 'notice-error');
            })
            .always(function () {
                IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner-settings', false);
                IAPOSTGROQ_UI.setButton('#iapostgroq-btn-save-settings', false, 'Guardar configuración');
            });
        },

        onTestApi: function () {
            this.setSpinner('#iapostgroq-spinner-settings', true);
            this.setButton('#iapostgroq-btn-test-api', true, IAPOSTGROQ.strings.testing);

            // Save current values first so the test uses them
            $.post(IAPOSTGROQ.ajax_url, {
                action:  'iapostgroq_save_settings',
                nonce:   IAPOSTGROQ.nonce,
                api_key: $('#iapostgroq-api-key').val(),
                model:   $('#iapostgroq-model-select').val()
            }).done(function () {
                $.post(IAPOSTGROQ.ajax_url, { action: 'iapostgroq_test_api', nonce: IAPOSTGROQ.nonce })
                .done(function (response) {
                    var msg = response.data ? response.data.message : IAPOSTGROQ.strings.error;
                    IAPOSTGROQ_UI.showSettingsMsg(msg, response.success ? 'notice-success' : 'notice-error');
                })
                .fail(function () {
                    IAPOSTGROQ_UI.showSettingsMsg(IAPOSTGROQ.strings.error, 'notice-error');
                })
                .always(function () {
                    IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner-settings', false);
                    IAPOSTGROQ_UI.setButton('#iapostgroq-btn-test-api', false, 'Verificar conexión');
                });
            });
        },

        onSaveDefaults: function () {
            this.setSpinner('#iapostgroq-spinner-defaults', true);
            this.setButton('#iapostgroq-btn-save-defaults', true, IAPOSTGROQ.strings.saving);

            $.post(IAPOSTGROQ.ajax_url, {
                action:          'iapostgroq_save_settings',
                nonce:           IAPOSTGROQ.nonce,
                api_key:         $('#iapostgroq-api-key').val() || '',
                model:           $('#iapostgroq-model-select').val() || '',
                content_type:    $('#iapostgroq-default-content-type').val(),
                journalist_role: $('#iapostgroq-default-journalist-role').val()
            })
            .done(function (response) {
                var msg = response.data ? response.data.message : IAPOSTGROQ.strings.error;
                IAPOSTGROQ_UI.showSettingsMsg(msg, response.success ? 'notice-success' : 'notice-error');
            })
            .fail(function () {
                IAPOSTGROQ_UI.showSettingsMsg(IAPOSTGROQ.strings.error, 'notice-error');
            })
            .always(function () {
                IAPOSTGROQ_UI.setSpinner('#iapostgroq-spinner-defaults', false);
                IAPOSTGROQ_UI.setButton('#iapostgroq-btn-save-defaults', false, 'Guardar defaults');
            });
        },

        // ── Custom promptbooks CRUD ───────────────────────────────────────────

        initCustomPromptbooks: function () {
            var self = this;

            // Auto-generate slug from label
            $(document).on('input', '.iapostgroq-new-label', function () {
                var pbType = $(this).data('pb-type');
                var slug   = self.slugify($(this).val());
                $('#iapostgroq-new-key-' + pbType).val(slug);
            });

            // Save
            $(document).on('click', '.iapostgroq-save-custom', function () {
                var pbType  = $(this).data('pb-type');
                var key     = $('#iapostgroq-new-key-'    + pbType).val().trim();
                var label   = $('#iapostgroq-new-label-'  + pbType).val().trim();
                var prompt  = $('#iapostgroq-new-prompt-' + pbType).val().trim();

                if (!key || !label || !prompt) {
                    alert('Nombre, clave e instrucciones son obligatorios.');
                    return;
                }

                self.setSpinner('.iapostgroq-spinner-pb-' + pbType, true);
                $(this).prop('disabled', true).text(IAPOSTGROQ.strings.saving);

                $.post(IAPOSTGROQ.ajax_url, {
                    action:          'iapostgroq_save_custom_promptbook',
                    nonce:           IAPOSTGROQ.nonce,
                    promptbook_type: pbType,
                    key:             key,
                    label:           label,
                    prompt:          prompt
                })
                .done(function (response) {
                    var msg = response.data ? response.data.message : IAPOSTGROQ.strings.error;
                    IAPOSTGROQ_UI.showSettingsMsg(msg, response.success ? 'notice-success' : 'notice-error');
                    if (response.success) {
                        setTimeout(function () { window.location.reload(); }, 800);
                    }
                })
                .fail(function () {
                    IAPOSTGROQ_UI.showSettingsMsg(IAPOSTGROQ.strings.error, 'notice-error');
                })
                .always(function () {
                    self.setSpinner('.iapostgroq-spinner-pb-' + pbType, false);
                    $('.iapostgroq-save-custom[data-pb-type="' + pbType + '"]').prop('disabled', false).text('Guardar');
                });
            });

            // Edit: populate form
            $(document).on('click', '.iapostgroq-edit-custom', function () {
                var pbType = $(this).data('pb-type');
                var key    = $(this).data('key');
                var label  = $(this).data('label');
                var prompt = $(this).data('prompt');

                $('#iapostgroq-new-label-'  + pbType).val(label);
                $('#iapostgroq-new-key-'    + pbType).val(key).prop('readonly', true);
                $('#iapostgroq-new-prompt-' + pbType).val(prompt);

                $('#iapostgroq-form-title-' + pbType).text('Editando: ' + label);
                $('.iapostgroq-cancel-edit[data-pb-type="' + pbType + '"]').show();

                // Scroll to form
                var $form = $('#iapostgroq-new-label-' + pbType);
                $('html, body').animate({ scrollTop: $form.offset().top - 80 }, 400);
                $form.focus();
            });

            // Cancel edit
            $(document).on('click', '.iapostgroq-cancel-edit', function () {
                var pbType = $(this).data('pb-type');
                self.resetPbForm(pbType);
            });

            // Delete
            $(document).on('click', '.iapostgroq-delete-custom', function () {
                if (!confirm(IAPOSTGROQ.strings.confirm_delete)) return;

                var pbType = $(this).data('pb-type');
                var key    = $(this).data('key');
                var $btn   = $(this);

                $btn.prop('disabled', true).text(IAPOSTGROQ.strings.deleting);

                $.post(IAPOSTGROQ.ajax_url, {
                    action:          'iapostgroq_delete_custom_promptbook',
                    nonce:           IAPOSTGROQ.nonce,
                    promptbook_type: pbType,
                    key:             key
                })
                .done(function (response) {
                    var msg = response.data ? response.data.message : IAPOSTGROQ.strings.error;
                    IAPOSTGROQ_UI.showSettingsMsg(msg, response.success ? 'notice-success' : 'notice-error');
                    if (response.success) {
                        $('#iapostgroq-row-' + pbType + '-' + key).fadeOut(400, function () {
                            $(this).remove();
                        });
                    }
                })
                .fail(function () {
                    IAPOSTGROQ_UI.showSettingsMsg(IAPOSTGROQ.strings.error, 'notice-error');
                    $btn.prop('disabled', false).text('Eliminar');
                });
            });
        },

        resetPbForm: function (pbType) {
            $('#iapostgroq-new-label-'  + pbType).val('');
            $('#iapostgroq-new-key-'    + pbType).val('').prop('readonly', false);
            $('#iapostgroq-new-prompt-' + pbType).val('');
            var isType = (pbType === 'content_type');
            $('#iapostgroq-form-title-' + pbType).text(isType ? 'Añadir tipo de contenido' : 'Añadir rol de periodista');
            $('.iapostgroq-cancel-edit[data-pb-type="' + pbType + '"]').hide();
        },

        slugify: function (text) {
            return text.toLowerCase()
                .replace(/[áàäâã]/g, 'a').replace(/[éèëê]/g, 'e')
                .replace(/[íìïî]/g,  'i').replace(/[óòöôõ]/g, 'o')
                .replace(/[úùüû]/g,  'u').replace(/ñ/g, 'n')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        // ── Helpers ───────────────────────────────────────────────────────────

        populateResults: function (data) {
            $('#iapostgroq-keyword').val(data.keyword               || '');
            $('#iapostgroq-title').val(data.title                   || '');
            $('#iapostgroq-slug').val(data.slug                     || '');
            $('#iapostgroq-seo-title').val(data.seo_title           || '');
            $('#iapostgroq-metadesc').val(data.meta_description     || '');
            $('#iapostgroq-og-title').val(data.og_title             || '');
            $('#iapostgroq-og-description').val(data.og_description || '');
            $('#iapostgroq-twitter-title').val(data.twitter_title   || '');
            $('#iapostgroq-twitter-description').val(data.twitter_description || '');
            $('#iapostgroq-social').val(data.social_post            || '');
            $('#iapostgroq-content').val(data.content               || '');

            // Trigger all counters
            $('#iapostgroq-seo-title, #iapostgroq-metadesc, #iapostgroq-og-title, #iapostgroq-og-description, #iapostgroq-twitter-title, #iapostgroq-twitter-description').trigger('input');
        },

        setSpinner: function (sel, show) {
            $(sel).css('visibility', show ? 'visible' : 'hidden');
        },

        setButton: function (sel, disabled, label) {
            $(sel).prop('disabled', disabled).text(label);
        },

        showSuccessPopup: function (editUrl) {
            var self = this;
            var count = 3;

            $('#iapostgroq-edit-now-link').attr('href', editUrl);
            $('#iapostgroq-redirect-count').text(count);
            $('#iapostgroq-success-overlay').fadeIn(300);

            // Countdown + redirect
            self._redirectTimer = setInterval(function () {
                count--;
                $('#iapostgroq-redirect-count').text(count);
                if (count <= 0) {
                    clearInterval(self._redirectTimer);
                    window.location.href = editUrl;
                }
            }, 1000);

            // "Editar ahora" — redirect immediately
            $('#iapostgroq-edit-now-link').off('click').on('click', function (e) {
                e.preventDefault();
                clearInterval(self._redirectTimer);
                window.location.href = editUrl;
            });

            // "Quedarse aquí" — cancel redirect, close popup
            $('#iapostgroq-stay-here').off('click').on('click', function () {
                clearInterval(self._redirectTimer);
                $('#iapostgroq-success-overlay').fadeOut(200);
                $('#iapostgroq-redirect-msg').text('Borrador guardado. Puedes seguir editando aquí.');
            });
        },

        showError: function (msg) {
            var $el = $('#iapostgroq-error');
            if (!msg) { $el.hide(); return; }
            $el.html('<p>' + msg + '</p>').show();
        },

        showSuccess: function (msg) {
            var $el = $('#iapostgroq-success');
            if (!msg) { $el.hide(); return; }
            $el.html('<p>' + msg + '</p>').show();
        },

        showSettingsMsg: function (msg, cssClass) {
            var $el = $('#iapostgroq-settings-msg');
            $el.removeClass('notice-success notice-error notice-warning')
               .addClass(cssClass)
               .html('<p>' + msg + '</p>')
               .show();
        }
    };

    $(document).ready(function () {
        IAPOSTGROQ_UI.init();
    });

}(jQuery));
