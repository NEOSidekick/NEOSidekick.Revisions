import React, { useCallback, useMemo } from 'react';
import { Icon } from '@neos-project/react-ui-components';

import { formatChangeDate } from '../Helpers/format';
import AssetPropertyDiff from './Diff/AssetPropertyDiff';
import ImagePropertyDiff from './Diff/ImagePropertyDiff';
import DateTimePropertyDiff from './Diff/DateTimePropertyDiff';
import TextPropertyDiff from './Diff/TextPropertyDiff';
import NodePropertyDiff from './Diff/NodePropertyDiff';
import FallbackPropertyDiff from './Diff/FallbackPropertyDiff';

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

    const renderChange = useCallback(({type, original, changed, diff}: NodeChange) => {
        switch (type) {
            case 'text':
                return <TextPropertyDiff original={original as string} changed={changed as string} diff={diff} />
            case 'image':
                return <ImagePropertyDiff original={original as string} changed={changed as string} />
            case 'asset':
                return <AssetPropertyDiff original={original as AssetProperty} changed={changed as AssetProperty} />
            case 'datetime':
                return <DateTimePropertyDiff original={original as string} changed={changed as string} />
            case 'node':
                return <NodePropertyDiff original={original as string} changed={changed as string} />
        }
        return <FallbackPropertyDiff original={original as string} changed={changed as string} />
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
                        <div>{translate('diff.propertyLabel', change.propertyLabel, { propertyLabel: change.propertyLabel })}</div>
                        <table style={{ width: '100%', borderSpacing: 0 }}>
                            <thead>
                                <tr>
                                    <th style={{ textAlign: 'left' }}>{translate('diff.old')}</th>
                                    <th style={{ textAlign: 'left' }}>{translate('diff.new')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {renderChange(change)}
                            </tbody>
                        </table>
                    </div>
                );
            })}
        </div>
    );
};

export default React.memo(ContentChangeDiff);
