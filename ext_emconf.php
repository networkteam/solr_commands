<?php

$EM_CONF['solr_commands'] = [
    'title' => 'Solr Commands',
    'description' => 'Cli commands for EXT:solr. IndexQueue initialisation. Indexing. Garbage collection. All sites at once.',
    'category' => 'cli',
    'state' => 'stable',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
            'solr' => '11.0.0-11.99.99',
        ],
    ],
];
