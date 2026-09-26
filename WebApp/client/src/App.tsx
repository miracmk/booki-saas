import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import NotFound from "@/pages/NotFound";
import { Route, Switch } from "wouter";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import Home from "./pages/Home";
import SectorPage from "./pages/SectorPage";
import FeaturePage from "./pages/FeaturePage";
import ComparisonPage from "./pages/ComparisonPage";
import PricingPage from "./pages/PricingPage";
import HowItWorksPage from "./pages/HowItWorksPage";
import ContactPage from "./pages/ContactPage";
import LegalPage from "./pages/LegalPage";

function Router() {
  return (
    <Switch>
      {/* Home Route */}
      <Route path="/" component={Home} />

      {/* Sector Dynamic Landing Pages */}
      <Route path="/sektorler/:slug" component={SectorPage} />

      {/* Feature Dynamic Landing Pages */}
      <Route path="/ozellikler/:slug" component={FeaturePage} />

      {/* Competitor Comparison (20+ Competitors) */}
      <Route path="/karsilastirma" component={ComparisonPage} />

      {/* Pricing & Packages */}
      <Route path="/fiyatlar" component={PricingPage} />

      {/* How It Works & Onboarding Funnel */}
      <Route path="/nasil-calisir" component={HowItWorksPage} />

      {/* Contact & Demo Booking */}
      <Route path="/iletisim" component={ContactPage} />

      {/* Legal Pages */}
      <Route path="/privacy" component={LegalPage} />
      <Route path="/terms" component={LegalPage} />
      <Route path="/gizlilik" component={LegalPage} />
      <Route path="/kullanim" component={LegalPage} />
      <Route path="/data-deletion" component={LegalPage} />
      <Route path="/data-deletion-instructions" component={LegalPage} />
      <Route path="/hakkimizda" component={LegalPage} />
      <Route path="/mesafeli-satis" component={LegalPage} />
      <Route path="/teslimat-iade" component={LegalPage} />

      {/* 404 Routes */}
      <Route path="/404" component={NotFound} />
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light">
        <TooltipProvider>
          <Toaster />
          <Router />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
