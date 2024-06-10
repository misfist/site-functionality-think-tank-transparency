import {
	BlockIcon,
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	BlockControls,
	MediaPlaceholder,
	MediaReplaceFlow,
	withColors,
	URLInput,
	ColorPalette,
	MediaUpload,
} from '@wordpress/block-editor';
import { Button, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import classNames from 'classnames';

const ALLOWED_MEDIA_TYPES = [ 'image' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			placeholder: __( 'Add Heading...', 'site-functionality' ),
			level: 2,
			className: 'hero__title h1',
		},
		[],
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add Content...', 'site-functionality' ),
			className: 'hero__content',
		},
		[],
	],
	[
		'core/buttons',
		{
			className: 'hero__buttons',
		},
		[ [ 'core/button', BUTTON_ATTRS, [] ] ],
	],
];

const ALLOWED_BLOCKS = [ 'core/heading', 'core/paragraph', 'core/buttons' ];

const Edit = ( props ) => {
	const {
		attributes,
		className,
		setAttributes,
	} = props;

	const blockProps = useBlockProps( {
		className: classNames( className, 'hero' ),
	} );

	return (
		<div { ...blockProps }
			>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ TEMPLATE }
				templateLock="all"
			/>
		</div>
	);
};

export default Edit;
