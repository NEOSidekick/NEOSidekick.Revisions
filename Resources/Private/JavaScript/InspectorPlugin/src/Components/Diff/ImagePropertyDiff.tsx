import React from 'react';

type DiffProps = {
    encodedImageData: string | null;
};

const ImagePropertyDiff: React.FC<DiffProps> = ({ encodedImageData }) => {
    let imageData: AssetProperty = null;

    if (encodedImageData) {
        try {
            imageData = JSON.parse(encodedImageData);
        } catch (e) {}
    }

    return imageData?.src ? (
        <img src={imageData.src} alt={imageData.alt} title={imageData.filename} />
    ) : (
        <p>{encodedImageData}</p>
    );
};

export default React.memo(ImagePropertyDiff);
