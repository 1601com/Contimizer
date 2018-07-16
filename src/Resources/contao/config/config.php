<?php

\System::loadLanguageFile('tl_modules');

/**
 * Back end modules
 */

$GLOBALS['BE_MOD']['system']['contimizer'] = array(
    'callback' => '\agentur1601com\Contimizer\modules\Contimizer'
);

// Style sheet
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/contimizer/style.css';
}