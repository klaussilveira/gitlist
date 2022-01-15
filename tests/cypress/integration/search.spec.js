describe('Repository search', () => {
  it('successfully searches', () => {
    cy.visit('/git-bare-repo/search/commits/master');

    cy.get('#criteria_message').clear();
    cy.get('#criteria_message').type('mm');
    cy.get('#criteria_submit').click();
    cy.get('.me-auto > [href="/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed"]').should('have.text', 'Fixed mm.');
    cy.get('.me-auto > [href="/git-bare-repo/commit/5570c142146e430b7356a84175f281ab2a364d48"]').should('have.text', 'Added mm.');
  });
});
