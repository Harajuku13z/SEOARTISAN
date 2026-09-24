import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster, toast } from 'sonner';
import 'sonner/dist/styles.css';

const FORM_TOAST_ID = 'ets-form-status';
function scrollToQuote() {
  const target = document.querySelector('#devis, [data-quote-form], form[data-ajax-form]');
  toast.dismiss();
  if (!target) { window.location.href = '/contact#devis'; return; }
  target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  window.setTimeout(() => {
    const field = target.querySelector('input:not([type="hidden"]), select, textarea');
    if (field) field.focus({ preventScroll: true });
  }, 650);
}
function QuoteInvitation({ toastId }) {
  const invite = window.__siteInvite || {};
  return <div className="ets-toast" role="status"><div className="ets-toast__accent"/><div className="ets-toast__body">
    <button className="ets-toast__close" type="button" aria-label="Fermer" onClick={() => toast.dismiss(toastId)}>×</button>
    <span className="ets-toast__label">{invite.label || 'Devis gratuit'}</span><h2 className="ets-toast__title">{invite.title || 'Un projet en tête ?'}</h2>
    <p className="ets-toast__text">{invite.text || 'Décrivez votre besoin en quelques instants. Nous vous rappelons rapidement.'}</p>
    <div className="ets-toast__actions"><button className="ets-toast__action" type="button" onClick={scrollToQuote}>Demander mon devis</button>{invite.phone ? <a className="ets-toast__phone" href={'tel:' + invite.phone}>Appeler maintenant</a> : null}</div>
  </div></div>;
}
function Notifications() {
  useEffect(() => {
    if (sessionStorage.getItem('ets_form_success') === '1') { sessionStorage.removeItem('ets_form_success'); toast.success('Votre demande a bien été envoyée. Nous vous recontactons rapidement.'); }
    let invitationTimer;
    if (document.body.classList.contains('page-home') && !sessionStorage.getItem('ets_home_invite_v1')) {
      invitationTimer = window.setTimeout(() => { sessionStorage.setItem('ets_home_invite_v1', '1'); toast.custom((id) => <QuoteInvitation toastId={id}/>, { duration: 12000 }); }, 1400);
    }
    const submitting = () => toast.loading('Envoi de votre demande…', { id: FORM_TOAST_ID });
    const failed = (event) => toast.error(event.detail?.message || 'Une erreur est survenue.', { id: FORM_TOAST_ID });
    document.addEventListener('ets:form-submitting', submitting); document.addEventListener('ets:form-error', failed);
    return () => { window.clearTimeout(invitationTimer); document.removeEventListener('ets:form-submitting', submitting); document.removeEventListener('ets:form-error', failed); };
  }, []);
  return <Toaster position="bottom-left" richColors closeButton={false} expand={false} visibleToasts={3}/>;
}
const root = document.getElementById('sonner-root');
if (root) createRoot(root).render(<Notifications/>);
