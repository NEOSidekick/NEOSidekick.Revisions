import React from 'react';

type DiffProps = {
    value: string;
};

const NodePropertyDiff: React.FC<DiffProps> = ({ value }) => {
    return <span>{value}</span>;
};

export default React.memo(NodePropertyDiff);
