<?php

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] =
    ['terminal42_password_validation.listener.no_password_change_page_warning', 'tlPageShowWarning'];

$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace(
    ';{publish_legend}',
    ';{pwChangeLegend},pwChangePage;{publish_legend}',
    $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
);

$GLOBALS['TL_DCA']['tl_page']['fields']['pwChangePage'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_page']['pwChangePage'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
    'relation'   => ['type' => 'hasOne', 'load' => 'eager']
];
