import React from 'react';

type VisualDiffProps = {
    diff: TextDiff;
};

const VisualDiff: React.FC<VisualDiffProps> = ({ diff }) => {
    return Array.isArray(diff) ? (
        <>
            {diff.map((blocks) =>
                blocks.map((block, index) => (
                    <tr key={index}>
                        <td
                            dangerouslySetInnerHTML={{
                                __html: block.base.lines.join(),
                            }}
                            style={{ color: '#ff460d', textAlign: 'left' }}
                        />
                        <td
                            dangerouslySetInnerHTML={{
                                __html: block.changed.lines.join(),
                            }}
                            style={{ color: '#00a338', textAlign: 'left' }}
                        />
                    </tr>
                ))
            )}
        </>
    ) : <p>Cannot render visual diff</p>;
};

export default React.memo(VisualDiff);
