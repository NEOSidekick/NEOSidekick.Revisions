import React, { useCallback, useMemo } from 'react';
import { Icon } from '@neos-project/react-ui-components';

import { formatChangeDate } from '../Helpers/format';
import AssetPropertyDiff from './Diff/AssetPropertyDiff';
import ImagePropertyDiff from './Diff/ImagePropertyDiff';
import DateTimePropertyDiff from './Diff/DateTimePropertyDiff';
import TextPropertyDiff from './Diff/TextPropertyDiff';
import NodePropertyDiff from './Diff/NodePropertyDiff';
import FallbackPropertyDiff from './Diff/FallbackPropertyDiff';
import VisualDiff from './Diff/VisualDiff';

type ContentChangeDiffProps = {
    nodeChanges: NodeChanges;
    contentDimensions: ContentDimensions;
    translate: (id: string, fallback?: string, params?: Record<string, unknown> | string[]) => string;
};

const ContentChangeDiff: React.FC<ContentChangeDiffProps> = ({ nodeChanges, contentDimensions, translate }) => {
    const { node, type, changes = [] } = nodeChanges;

    const changeColor = useMemo(() => {
        return type === 'changeNode' ? '#ff8700' : type === 'addNode' ? '#00a338' : '#ff460d';
    }, [type]);

    const dimensionLabel = useMemo(() => {
        return (
            <>
                {Object.keys(node.dimensions).map((dimensionName) => {
                    const contentDimension = contentDimensions[dimensionName];
                    if (!contentDimension) {
                        return null;
                    }
                    const presetKey = node.dimensions[dimensionName][0];
                    return presetKey ? (
                        <span key={dimensionName} title={`${translate(contentDimension.label)}: ${presetKey}`}>
                            {contentDimension.icon && (
                                <Icon icon={contentDimension.icon} style={{ marginRight: '0.5em', color: '#222' }} />
                            )}
                            {translate(contentDimension.presets[presetKey].label)}
                        </span>
                    ) : null;
                })}
            </>
        );
    }, [node.dimensions]);

    const renderChange = useCallback((text: string, type: ChangeType) => {
        switch (type) {
            case 'text':
                return <TextPropertyDiff text={text} />;
            case 'image':
                return <ImagePropertyDiff encodedImageData={text} />;
            case 'asset':
                return <AssetPropertyDiff encodedAssetData={text} />;
            case 'datetime':
                return <DateTimePropertyDiff encodedDateTimeData={text} />;
            case 'array':
                try {
                    const changes = JSON.parse(text);
                    return (
                        <ul>
                            {changes.map((change: string, index: number) => {
                                return (
                                    <li key={index}>
                                        <FallbackPropertyDiff value={change} />
                                    </li>
                                );
                            })}
                        </ul>
                    );
                } catch (e) {
                    return <FallbackPropertyDiff value={text} />;
                }
            case 'node':
                return <NodePropertyDiff value={text} />;
        }
        return <FallbackPropertyDiff value={text} />;
    }, []);

    return (
        <div
            style={{
                background: '#eee',
                color: '#252525',
                marginBottom: '1rem',
                padding: '1rem 1rem',
                borderLeft: `3px solid ${changeColor}`,
            }}
        >
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>
                    <strong>{translate('diff.' + type)}</strong>
                    {node.nodeType.icon && (
                        <Icon
                            icon={node.nodeType.icon}
                            title={node.nodeType.label ? translate(node.nodeType.label) : node.nodeType.name}
                            style={{ margin: '0 0.5em', color: '#222' }}
                            color="primaryBlue"
                        />
                    )}
                    {node.label}
                </span>
                <span>
                    {dimensionLabel}
                    <Icon icon="clock" style={{ margin: '0 0.5em', color: '#222' }} />
                    {formatChangeDate(node.lastModificationDateTime)}
                </span>
            </div>
            {Object.keys(changes).map((propertyName) => {
                const change = changes[propertyName];
                return (
                    <div
                        key={propertyName}
                        style={{ borderTop: '1px solid #323232', marginTop: '1rem', padding: '1rem' }}
                    >
                        <div>
                            {translate('diff.propertyLabel', change.propertyLabel, {
                                propertyLabel: change.propertyLabel,
                            })}
                        </div>
                        <table style={{ width: '100%', borderSpacing: 0 }}>
                            <thead>
                                <tr>
                                    <th style={{ textAlign: 'left', width: '50%' }}>{translate('diff.old')}</th>
                                    <th style={{ textAlign: 'left', width: '50%' }}>{translate('diff.new')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Array.isArray(change.diff) && change.diff.length > 0 ? (
                                    <VisualDiff diff={change.diff} />
                                ) : (
                                    <tr>
                                        <td style={{ color: '#ff460d' }}>
                                            {renderChange(change.original, change.originalType)}
                                        </td>
                                        <td style={{ color: '#00a338' }}>
                                            {renderChange(change.changed, change.changedType)}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                );
            })}
        </div>
    );
};

export default React.memo(ContentChangeDiff);
