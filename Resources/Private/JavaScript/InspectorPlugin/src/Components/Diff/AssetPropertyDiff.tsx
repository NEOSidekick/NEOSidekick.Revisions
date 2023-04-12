import React from 'react';

type DiffProps = {
    original: AssetProperty;
    changed: AssetProperty;
};

const AssetPropertyDiff: React.FC<DiffProps> = ({ original, changed }) => {
    return (
        <tr>
            <td>
                {original?.resource && (
                    <del>
                        <a href={original.resource.uri} target="_blank">
                            {original.resource.filename}
                        </a>
                    </del>
                )}
            </td>
            <td>
                {changed?.resource && (
                    <ins>
                        <a href={changed.resource.uri} target="_blank">
                            {changed.resource.filename}
                        </a>
                    </ins>
                )}
            </td>
        </tr>
    );
};

export default React.memo(AssetPropertyDiff);
