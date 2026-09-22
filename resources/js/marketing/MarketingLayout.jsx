import MarketingFooter from './components/MarketingFooter';
import MarketingHeader from './components/MarketingHeader';

export default function MarketingLayout({ children }) {
    return (
        <div className="iqp-marketing iqp-marketing-bg min-h-screen">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-violet-600 focus:px-4 focus:py-2 focus:text-white"
            >
                Skip to content
            </a>
            <MarketingHeader />
            <main id="main-content">{children}</main>
            <MarketingFooter />
        </div>
    );
}
