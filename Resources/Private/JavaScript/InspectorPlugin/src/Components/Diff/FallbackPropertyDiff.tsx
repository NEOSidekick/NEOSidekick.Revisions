import React from 'react';

type DiffProps = {
    value: string;
};

const FallbackPropertyDiff: React.FC<DiffProps> = ({ value }) => {
    return (
        <div style={{ overflow: 'auto', maxWidth: 'calc(100% - 1rem)' }}>
            <pre style={{ display: 'table', width: '100%', tableLayout: 'fixed' }}>{value}</pre>
        </div>
    );
};

export default React.memo(FallbackPropertyDiff);
