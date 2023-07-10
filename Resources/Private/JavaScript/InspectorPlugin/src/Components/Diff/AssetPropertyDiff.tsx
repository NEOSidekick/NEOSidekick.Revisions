import React from 'react';

type DiffProps = {
    encodedAssetData: string | null;
};

const AssetPropertyDiff: React.FC<DiffProps> = ({ encodedAssetData }) => {
    let assetData: AssetProperty = null;

    if (encodedAssetData) {
        try {
            assetData = JSON.parse(encodedAssetData);
        } catch (e) {}
    }

    return assetData?.src ? (
        <a href={assetData.src} target="_blank" title={assetData.alt}>
            {assetData.filename}
        </a>
    ) : (
        <p>{encodedAssetData}</p>
    );
};

export default React.memo(AssetPropertyDiff);
