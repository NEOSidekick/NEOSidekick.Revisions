import React from 'react';

type DiffProps = {
    text: string;
};

const TextPropertyDiff: React.FC<DiffProps> = ({ text }) => {
    return <span>{text ? text : '-'}</span>;
};

export default React.memo(TextPropertyDiff);
