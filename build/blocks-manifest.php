<?php
// This file is generated. Do not modify it manually.
return array(
	'anchor' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'blockparty/anchor',
		'version' => '1.0.2',
		'title' => 'Anchor',
		'category' => 'widgets',
		'description' => 'Place an anchor on your page. It will be listed in the anchor list block.',
		'attributes' => array(
			'title' => array(
				'type' => 'string'
			),
			'slug' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'example' => array(
			'attributes' => array(
				'title' => 'My anchor',
				'slug' => 'my-anchor'
			)
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'blockparty-anchors',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css'
	),
	'anchors-list' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'blockparty/anchors-list',
		'version' => '1.0.2',
		'title' => 'Anchors List',
		'category' => 'widgets',
		'description' => 'Displays the list of anchors placed on your page.',
		'attributes' => array(
			'slug' => array(
				'default' => '',
				'type' => 'string'
			),
			'title' => array(
				'type' => 'string'
			)
		),
		'example' => array(
			'attributes' => array(
				'title' => 'My anchor',
				'slug' => 'my-anchor'
			)
		),
		'supports' => array(
			'color' => array(
				'background' => true,
				'link' => true,
				'text' => true
			),
			'html' => false,
			'multiple' => false,
			'position' => array(
				'sticky' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'fontStyle' => true,
				'fontWeight' => true,
				'letterSpacing' => true,
				'lineHeight' => true,
				'textTransform' => true,
				'fontFamily' => true
			)
		),
		'textdomain' => 'blockparty-anchors',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css'
	)
);
