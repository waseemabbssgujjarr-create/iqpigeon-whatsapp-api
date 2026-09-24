import FirstApiRequestGuide from '../../../components/FirstApiRequestGuide';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function CodeExamples({ apiBaseUrl, connectionUuid }) {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Code examples">
                <FirstApiRequestGuide apiBaseUrl={apiBaseUrl} connectionId={connectionUuid} />
            </BuildCrmLayout>
        </AppLayout>
    );
}
