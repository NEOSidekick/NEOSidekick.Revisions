type ContentDimensions = Record<
    string,
    {
        label: string;
        icon: string;
        default: string;
        defaultPreset: string;
        presets: Record<
            string,
            {
                label: string;
                uriSegment: string;
                values: string[];
            }
        >;
    }
>;

type ImageProperty = {
    src: string;
    alt: string;
    title: string;
};

type AssetProperty = {
    resource: {
        uri: string;
        filename: string;
    };
};

type NodePropertyName = string;
type NodePropertyValue = string | number | boolean | null | ImageProperty | AssetProperty;

type TextDiff = {
    tag: string;
    base: {
        offset: number;
        lines: string[];
    };
    changed: {
        offset: number;
        lines: string[];
    };
}[][];

type NodeChange = {
    type: 'text' | 'asset' | 'image' | 'datetime' | 'node' | 'array' | 'nodeAdded' | 'nodeRemoved';
    propertyLabel: string;
    original: NodePropertyValue;
    changed: NodePropertyValue;
    diff: TextDiff;
};

type NodeChanges = {
    type: 'addNode' | 'removedNode' | 'changeNode';
    node: {
        label: string;
        lastModificationDateTime: string;
        dimensions: Record<
            string,
            {
                presets: Record<string, { label: string }>;
            }
        >;
        nodeType: {
            name: string;
            label: string;
            icon: string;
        };
    };
    changes: Record<NodePropertyName, NodeChange>;
};

type ChangeList = {
    [nodeIdentifier: string]: {
        [dimensionHash: string]: NodeChanges;
    }
};
