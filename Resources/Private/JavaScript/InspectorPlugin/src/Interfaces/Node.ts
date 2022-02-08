// Partial node interface copied from Neos.Ui
export default interface Node {
    contextPath: string;
    name: string;
    identifier: string;
    nodeType: string;
    label: string;
    depth: number;
    uri: string;
}
