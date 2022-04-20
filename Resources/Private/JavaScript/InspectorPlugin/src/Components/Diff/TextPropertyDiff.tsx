import React from 'react';

type DiffProps = {
    diff: TextDiff;
};

const TextPropertyDiff: React.FC<DiffProps> = ({ diff }) => {
    return (
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
    );
};

export default React.memo(TextPropertyDiff);
