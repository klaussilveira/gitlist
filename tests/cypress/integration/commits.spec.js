describe('Repository commits page', () => {
  it('successfully loads', () => {
    cy.visit('/git-bare-repo/commits/master');

    cy.get('.me-auto > [href="/git-bare-repo/commit/b064e711b341b3d160288cd121caf56811ca8991"]').should('have.text', 'Initial commit.');
  });

  it('view specific commit', () => {
    cy.visit('/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed');

    cy.get('.card-text').should('have.text', '\n              Klaus Silveira commited on 2016-11-24 12:30:04\n              Showing 1 changed files, with 44 additions and 173 deletions.\n            ');
    cy.get(':nth-child(5) > .line > .delete').should('have.text', '-#define CREATE_TRACE_POINTS');
  });

  it('view parent commit', () => {
    cy.visit('/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed');

    cy.get(':nth-child(1) > .col-12 > .card > .card-header > .btn-group > .btn').click();
    cy.get(':nth-child(1) > .col-12 > .card > .card-header').should('have.text', '\n          Added mm.\n\n          \n                          \n                 Parent 85e6568\n              \n                      \n        ');
  });
});
