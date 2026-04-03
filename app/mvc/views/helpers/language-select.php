<?php
/**
 * Helper function to render language select with flags
 * 
 * @param FormBuilder $form Form builder instance
 * @param array $languagesData Languages data array with flag codes
 * @param string $selectedLangId Currently selected language ID
 * @param string $fieldName Field name (default: 'language_id')
 * @return void
 */
function renderLanguageSelect($form, $languagesData, $selectedLangId = '', $fieldName = 'language_id') {
    if (empty($languagesData)) {
        return;
    }
    
    $form->raw('<div class="space-y-2">');
    $form->raw('<label class="block text-sm font-medium text-slate-300">Language</label>');
    $form->raw('<div class="relative">');
    $form->raw('<select name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldName) . '_select" class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary appearance-none pr-10">');
    $form->raw('<option value="">-- Select Language --</option>');
    
    foreach ($languagesData as $langId => $langData) {
        $selected = ($selectedLangId == $langId) ? ' selected' : '';
        $form->raw('<option value="' . htmlspecialchars($langId) . '" data-flag="' . htmlspecialchars($langData['flag_code']) . '"' . $selected . '>');
        $form->raw(htmlspecialchars($langData['name'] . ' (' . $langData['native_name'] . ')'));
        $form->raw('</option>');
    }
    
    $form->raw('</select>');
    
    // Flag icon display (will be updated by JavaScript)
    $selectedFlagCode = '';
    if ($selectedLangId && isset($languagesData[$selectedLangId])) {
        $selectedFlagCode = $languagesData[$selectedLangId]['flag_code'];
    }
    $form->raw('<div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" id="' . htmlspecialchars($fieldName) . '_flag_display">');
    if ($selectedFlagCode) {
        $form->raw('<span class="fi fi-' . htmlspecialchars($selectedFlagCode) . '" style="font-size: 1.25rem;"></span>');
    }
    $form->raw('</div>');
    $form->raw('</div>');
    
    if (Form::hasError($fieldName)) {
        $form->raw('<p class="text-red-400 text-sm mt-1">' . htmlspecialchars(Form::getError($fieldName)) . '</p>');
    }
    
    $form->raw('</div>');
    
    // JavaScript to update flag display
    $nonce = function_exists('csp_nonce') ? csp_nonce() : '';
    $form->raw('<script nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '">');
    $form->raw('(function() {');
    $form->raw('  const select = document.getElementById("' . htmlspecialchars($fieldName) . '_select");');
    $form->raw('  const flagDisplay = document.getElementById("' . htmlspecialchars($fieldName) . '_flag_display");');
    $form->raw('  if (select && flagDisplay) {');
    $form->raw('    select.addEventListener("change", function(e) {');
    $form->raw('      const selected = e.target.options[e.target.selectedIndex];');
    $form->raw('      const flagCode = selected?.dataset?.flag || "";');
    $form->raw('      flagDisplay.innerHTML = flagCode ? \'<span class="fi fi-\' + flagCode + \'" style="font-size: 1.25rem;"></span>\' : "";');
    $form->raw('    });');
    $form->raw('  }');
    $form->raw('})();');
    $form->raw('</script>');
}

