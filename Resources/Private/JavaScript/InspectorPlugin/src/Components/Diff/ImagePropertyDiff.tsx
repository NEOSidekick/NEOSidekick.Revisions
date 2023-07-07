import React from 'react';

type DiffProps = {
    original: string | null;
    changed: string | null;
};

const ImagePropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    let originalImage: ImageProperty = null;
    let changedImage: ImageProperty = null;

    if (original) {
        try {
            originalImage = JSON.parse(original);
        } catch (e) {}
    }
    if (changed) {
        try {
            changedImage = JSON.parse(changed);
        } catch (e) {}
    }

    return (
        <tr>
            <td>
                {originalImage?.src ? (
                    <img src={originalImage.src} alt={originalImage.alt} title={originalImage.title} />
                ) : (
                    '-'
                )}
            </td>
            <td>
                {changedImage?.src ? (
                    <img src={changedImage.src} alt={changedImage.alt} title={changedImage.title} />
                ) : (
                    '-'
                )}
            </td>
        </tr>
    );
};

export default React.memo(ImagePropertyDiff);
