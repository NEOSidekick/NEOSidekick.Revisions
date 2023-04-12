import React from 'react';

type DiffProps = {
    original: ImageProperty;
    changed: ImageProperty;
};

const ImagePropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    return (
        <tr>
            <td>
                {(original as ImageProperty)?.src ? <img src={original.src} alt={original.alt} title={original.title} /> : '-'}
            </td>
            <td>{changed?.src ? <img src={changed.src} alt={changed.alt} title={changed.title} /> : '-'}</td>
        </tr>
    );
};

export default React.memo(ImagePropertyDiff);
