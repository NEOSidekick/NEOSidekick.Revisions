import React from 'react';

type DiffProps = {
    original: string;
    changed: string;
};

const FallbackPropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    return (
        <tr>
            <td>
                <div style={{ overflow: 'auto', maxWidth: 'calc(100% - 1rem)' }}>
                    <pre style={{ display: 'table', width: '100%', tableLayout: 'fixed' }}>{original}</pre>
                </div>
            </td>
            <td>
                <div style={{ overflow: 'auto' }}>
                    <pre style={{ display: 'table', width: '100%', tableLayout: 'fixed' }}>{changed}</pre>
                </div>
            </td>
        </tr>
    );
};

export default React.memo(FallbackPropertyDiff);
