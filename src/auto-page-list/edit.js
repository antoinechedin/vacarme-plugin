/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	/* const pageId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);
	console.log(pageId); */

	/* const pages = useSelect(
		select =>
			select(coreDataStore).getEntityRecords('postType', 'page', { parent: 0 }),
		[]
	);

	const options = pages?.map(page => (
		{ value: page.slug, label: decodeEntities(page.title.rendered) }
	)); */


	return (
		<nav {...useBlockProps()}>
			<ul class="wp_block_page_list">
				<li class="page_item">{'Page 1'}</li>
				<li class="page_item">{'Page 2'}</li>
				<li class="page_item">{'Page 3'}
					<ul class="children">
						<li class="page_item">{'Page 2.1'}</li>
						<li class="page_item">{'Page 2.2'}</li>
						<li class="page_item">{'Page 2.3'}</li>
					</ul>
				</li>
				<li class="page_item">{'Page 4'}</li>
				<li class="page_item">{'Page 5'}
					<ul class="children">
						<li class="page_item">{'Page 2.1'}</li>
						<li class="page_item">{'Page 2.2'}</li>
						<li class="page_item">{'Page 2.3'}</li>
					</ul>
				</li>
			</ul>
		</nav>
	);
}
