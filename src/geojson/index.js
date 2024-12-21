import { __ } from '@wordpress/i18n';

import { registerBlockType } from '@wordpress/blocks';
//import './style.scss';
//import Edit from './edit';
import { CodeEdit } from '@wordpress/block-library';
import { TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { RichText, PlainText, useBlockProps } from '@wordpress/block-editor';
import block from './block.json';


registerBlockType(block.name, {
	edit: ({ setAttributes, attributes }) => {
		const blockProps = useBlockProps();
		//blockProps.className += " wp-block-code";
		const postType = useSelect(
			(select) => select('core/editor').getCurrentPostType(),
			[]
		);

		const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

		let geojsonString = meta['geojson'];
		try {
			geojsonString = JSON.stringify(JSON.parse(meta['geojson']), null, 2);
		} catch (e) {
			console.error(e); // #TODO: better error handling
		}

		const updateGeojsonString = (newValue) => {
			// #TODO: Tried to format the json string. Wasn't working very well
			/* try {
				newValue = JSON.stringify(JSON.parse(newValue));
			} catch (e) {
				console.error(e); // #TODO: better error handling
			} */
			setMeta({ ...meta, geojson: newValue }); // 
		};

		return (
			<TextareaControl {...blockProps}
				label='Geo JSON'
				// className='wp-block-code'
				value={geojsonString}
				onChange={updateGeojsonString}
				rows={20}
			/>
		);
	},
	save: () => {
		return null;
	}
});
